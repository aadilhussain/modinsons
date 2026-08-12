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

    protected $fillable = [
        'category_id', 'name', 'slug', 'brand', 'sku', 'short_description', 'description',
        'specs', 'unit', 'min_order_qty', 'image_path', 'badge',
        'is_active', 'is_featured', 'sort_order', 'views',
    ];

    protected $casts = [
        'specs' => 'array',
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
            return Storage::disk('public')->url($this->image_path);
        }

        $icon = $this->category?->icon ?: 'default';

        return asset("assets/img/{$icon}.svg");
    }

    /** True when a real uploaded photo backs this product. */
    public function hasPhoto(): bool
    {
        return $this->image_path && Storage::disk('public')->exists($this->image_path);
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
            ? Storage::disk('public')->url($this->image_path)
            : asset('assets/img/og-cover.png');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
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
