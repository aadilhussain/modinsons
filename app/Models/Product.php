<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    /** Used when a product has stock tracking on but no threshold of its own. */
    public const DEFAULT_LOW_STOCK_THRESHOLD = 5;

    protected $fillable = [
        'category_id', 'name', 'slug', 'brand', 'sku', 'short_description', 'description',
        'specs', 'unit', 'price', 'min_order_qty', 'stock_qty', 'low_stock_threshold',
        'image_path', 'image_source', 'badge',
        'is_active', 'is_featured', 'sort_order', 'views',
    ];

    /**
     * The internal rate is never published — the catalogue quotes on enquiry.
     * Hiding it here keeps it out of any accidental toJson()/toArray() on a
     * public page or API response, whatever a view happens to do.
     */
    protected $hidden = ['price'];

    protected $casts = [
        'specs' => 'array',
        'price' => 'decimal:2',
        'stock_qty' => 'integer',
        'low_stock_threshold' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $p) {
            if (blank($p->slug)) {
                $p->slug = Str::slug($p->name).'-'.Str::lower(Str::random(4));
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Uploaded image if present, otherwise the bundled category illustration. */
    public function getImageUrlAttribute(): string
    {
        if ($this->hasPhoto()) {
            return $this->photoUrl();
        }

        $icon = $this->category?->icon ?: 'default';

        return asset("assets/img/{$icon}.svg");
    }

    /** True when a real uploaded photo backs this product. */
    public function hasPhoto(): bool
    {
        if (! $this->image_path) {
            return false;
        }

        if ($this->isRemoteUrl()) {
            // Existence isn't cheaply checkable without a network round trip;
            // trust the DB record, matching how uploads and deletes keep it in sync.
            return true;
        }

        return $this->isCommittedAsset()
            ? is_file(public_path($this->image_path))
            : Storage::disk('public')->exists($this->image_path);
    }

    /**
     * Photos live in one of three places and all three have to keep working.
     *
     * Admin uploads on Vercel go to Vercel Blob (a full https:// URL), since
     * storage/ there is /tmp and is wiped between invocations — nothing
     * written to local disk survives past the request. Admin uploads without
     * a blob token configured (local/shared hosting) fall back to the public
     * storage disk. Bulk-fetched catalogue photos are committed under
     * public/assets/products instead, because a committed file is the only
     * one guaranteed to survive a deploy without external storage. The path
     * shape (assets/ prefix vs. http(s):// vs. anything else) is what tells
     * the three apart.
     */
    protected function isCommittedAsset(): bool
    {
        return str_starts_with((string) $this->image_path, 'assets/');
    }

    protected function isRemoteUrl(): bool
    {
        return str_starts_with((string) $this->image_path, 'http://')
            || str_starts_with((string) $this->image_path, 'https://');
    }

    protected function photoUrl(): string
    {
        if ($this->isRemoteUrl()) {
            return $this->image_path;
        }

        return $this->isCommittedAsset()
            ? asset($this->image_path)
            : Storage::disk('public')->url($this->image_path);
    }

    /**
     * A raster image URL for social cards, structured data and image sitemaps.
     *
     * The catalogue illustrations are SVGs, which Facebook, WhatsApp, X and
     * Google's rich results all refuse to render — products without a photo
     * fall back to the site's share card rather than a link with a blank image.
     */
    public function getSocialImageUrlAttribute(): string
    {
        return $this->hasPhoto()
            ? $this->photoUrl()
            : asset('assets/img/og-cover.png');
    }

    /** Null stock_qty means "not tracked" — never flagged as low or out. */
    public function getIsOutOfStockAttribute(): bool
    {
        return $this->stock_qty !== null && $this->stock_qty <= 0;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_qty !== null
            && $this->stock_qty > 0
            && $this->stock_qty <= ($this->low_stock_threshold ?? self::DEFAULT_LOW_STOCK_THRESHOLD);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereRaw('is_active = true');
    }

    public function scopeLowOrOutOfStock(Builder $q): Builder
    {
        return $q->whereNotNull('stock_qty')
            ->whereRaw('stock_qty <= COALESCE(low_stock_threshold, ?)', [self::DEFAULT_LOW_STOCK_THRESHOLD]);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (blank($term)) {
            return $q;
        }

        $t = '%'.trim($term).'%';

        return $q->where(function (Builder $w) use ($t) {
            $w->where('name', 'like', $t)
              ->orWhere('brand', 'like', $t)
              ->orWhere('short_description', 'like', $t)
              ->orWhere('sku', 'like', $t);
        });
    }
}
