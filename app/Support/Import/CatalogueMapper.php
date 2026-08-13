<?php

namespace App\Support\Import;

use App\Models\Category;
use App\Models\Product;
use App\Support\Import\CatalogueReader;
use Illuminate\Support\Str;

/**
 * Turns raw file rows into reviewable product candidates.
 *
 * Every row comes back with a verdict — create, update, skip or error — so the
 * import screen can show exactly what will happen before anything is written.
 * Nothing here saves; see CatalogueImporter.
 */
class CatalogueMapper
{
    /**
     * Column aliases, so a supplier's own spreadsheet headings work without
     * being renamed first. Keys are the fields we store.
     *
     * @var array<string, array<int, string>>
     */
    protected const ALIASES = [
        'name'              => ['name', 'product', 'product_name', 'item', 'item_name', 'title', 'description_short'],
        'sku'               => ['sku', 'code', 'model', 'model_no', 'model_number', 'item_code', 'product_code', 'article', 'art_no'],
        'category'          => ['category', 'cat', 'group', 'type', 'product_category', 'section'],
        'brand'             => ['brand', 'make', 'manufacturer', 'company'],
        'short_description' => ['short_description', 'summary', 'tagline', 'subtitle'],
        'description'       => ['description', 'details', 'long_description', 'notes', 'remarks'],
        'unit'              => ['unit', 'uom', 'selling_unit', 'measure'],
        'price'             => ['price', 'rate', 'mrp', 'cost', 'list_price', 'unit_price', 'dealer_price', 'net_rate'],
        'min_order_qty'     => ['min_order_qty', 'moq', 'minimum_order', 'min_qty', 'minimum_order_quantity'],
        'badge'             => ['badge', 'label', 'tag', 'highlight'],
        'sort_order'        => ['sort_order', 'order', 'position', 'sr_no', 'sr', 'serial'],
        'is_active'         => ['is_active', 'active', 'status', 'published', 'live'],
        'is_featured'       => ['is_featured', 'featured', 'highlight_on_home'],
        'image_source'      => ['image_url', 'image', 'image_path', 'photo', 'photo_url', 'picture', 'img'],
    ];

    /** Columns that carry no product meaning and should not become specs. */
    protected const IGNORED = ['id', 'slug', 'views', 'created_at', 'updated_at', 'url', 'link'];

    /** Units offered on the product form; anything else falls back to Piece. */
    protected const UNITS = ['Piece', 'Metre', 'Coil', 'Kilogram', 'Set', 'Bundle', 'Square Feet', 'Bag', 'Box'];

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function map(array $rows, string $defaultCategory = ''): array
    {
        $categories = Category::all();
        $existingBySku = Product::query()->whereNotNull('sku')->where('sku', '!=', '')
            ->get(['id', 'sku', 'name', 'category_id'])->keyBy(fn ($p) => $this->key($p->sku));
        $existingByName = Product::all(['id', 'sku', 'name', 'category_id'])
            ->keyBy(fn ($p) => $this->key($p->name));

        // Names repeated inside one file would otherwise each look importable
        // and collide on the way in.
        $seen = [];
        $mapped = [];

        foreach ($rows as $i => $row) {
            $line = (int) ($row[CatalogueReader::LINE_KEY] ?? $i + 2);
            unset($row[CatalogueReader::LINE_KEY]);

            $mapped[] = $this->mapRow($row, $line, $categories, $existingBySku, $existingByName, $seen, $defaultCategory);
        }

        return $mapped;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    protected function mapRow(
        array $row,
        int $line,
        $categories,
        $existingBySku,
        $existingByName,
        array &$seen,
        string $defaultCategory
    ): array {
        $get = function (string $field) use ($row): string {
            foreach (self::ALIASES[$field] ?? [] as $alias) {
                if (isset($row[$alias]) && trim($row[$alias]) !== '') {
                    return trim($row[$alias]);
                }
            }

            return '';
        };

        $errors = [];
        $name = $get('name');
        $sku = $get('sku');

        // Some catalogues only carry a model code; it is a usable name.
        if ($name === '' && $sku !== '') {
            $name = $sku;
        }

        if ($name === '') {
            $errors[] = 'No product name or code in this row.';
        }

        if (mb_strlen($name) > 180) {
            $name = mb_substr($name, 0, 180);
            $errors[] = 'Name was longer than 180 characters and has been shortened.';
        }

        $categoryName = $get('category') ?: $defaultCategory;

        if ($categoryName === '') {
            $errors[] = 'No category, and no fallback category was chosen.';
        }

        $category = $categoryName === '' ? null : $categories->first(
            fn ($c) => $this->key($c->name) === $this->key($categoryName) || $c->slug === Str::slug($categoryName)
        );

        // Everything not recognised as a field becomes a spec line, which is how
        // sizes, trap sizes, materials and finishes survive the import.
        $specs = [];
        $known = array_merge(...array_values(self::ALIASES));

        foreach ($row as $column => $value) {
            $value = trim((string) $value);

            if ($value === '' || in_array($column, $known, true) || in_array($column, self::IGNORED, true)) {
                continue;
            }

            $label = Str::title(str_replace('_', ' ', $column));
            $specs[mb_substr($label, 0, 60)] = mb_substr($value, 0, 160);
        }

        $data = [
            'name'              => $name,
            'sku'               => mb_substr($sku, 0, 60),
            'brand'             => mb_substr($get('brand'), 0, 80),
            'short_description' => mb_substr($get('short_description'), 0, 255),
            'description'       => mb_substr($get('description'), 0, 5000),
            'unit'              => $this->unit($get('unit')),
            'price'             => $this->price($get('price')),
            'min_order_qty'     => mb_substr($get('min_order_qty'), 0, 60),
            'badge'             => mb_substr($get('badge'), 0, 30),
            'sort_order'        => (int) preg_replace('/\D/', '', $get('sort_order')) ?: 0,
            'is_active'         => $this->boolish($get('is_active'), true),
            'is_featured'       => $this->boolish($get('is_featured'), false),
            'specs'            => $specs ?: null,
            // Only queued here, never downloaded during an import: a web
            // request that fetches a few hundred remote images times out long
            // before it finishes. `php artisan catalogue:images` collects them.
            'image_source'      => $this->imageSource($get('image_source')),
        ];

        // Match an existing product on code first — it is the stable identifier —
        // then fall back to the name.
        $existing = null;
        if ($sku !== '' && $existingBySku->has($this->key($sku))) {
            $existing = $existingBySku->get($this->key($sku));
        } elseif ($name !== '' && $existingByName->has($this->key($name))) {
            $existing = $existingByName->get($this->key($name));
        }

        $duplicateInFile = false;
        $fingerprint = $sku !== '' ? 'sku:'.$this->key($sku) : 'name:'.$this->key($name);

        if ($name !== '') {
            if (isset($seen[$fingerprint])) {
                $duplicateInFile = true;
                $errors[] = 'Same product appears earlier in this file (line '.$seen[$fingerprint].').';
            } else {
                $seen[$fingerprint] = $line;
            }
        }

        $action = match (true) {
            $errors !== [] && ($name === '' || $duplicateInFile) => 'error',
            $existing !== null => 'update',
            default => 'create',
        };

        return [
            'line'         => $line,
            'action'       => $action,
            'errors'       => $errors,
            'data'         => $data,
            'category'     => $categoryName,
            'category_id'  => $category?->id,
            'new_category' => $category === null && $categoryName !== '',
            'existing_id'  => $existing?->id,
        ];
    }

    /**
     * Only absolute http(s) URLs are worth queuing. A spreadsheet exported from
     * Excel often carries "C:\photos\1001.jpg" or a bare filename in the image
     * column, and neither is fetchable from the server.
     */
    protected function imageSource(string $value): ?string
    {
        return preg_match('~^https?://~i', $value) ? mb_substr($value, 0, 2048) : null;
    }

    protected function unit(string $value): string
    {
        if ($value === '') {
            return 'Piece';
        }

        foreach (self::UNITS as $unit) {
            if ($this->key($unit) === $this->key($value)) {
                return $unit;
            }
        }

        return mb_substr($value, 0, 40);
    }

    /**
     * Price cells arrive as "₹1,250.00", "1250/-" or "Rs. 1250" — keep the
     * number and drop the decoration. Blank stays null so an import without a
     * rate column does not wipe rates already entered.
     */
    protected function price(string $value): ?string
    {
        /*
         * Match the number itself rather than stripping punctuation, because
         * stripping leaves the full stop in "Rs. 1100" attached to the digits
         * and turns ₹1,100 into ₹0.11.
         *
         * Handles 1250, 1,250.00, 1,00,000.50 (lakh grouping) and 1250/-.
         */
        if (! preg_match('/\d+(?:,\d{2,3})*(?:\.\d{1,2})?/', $value, $m)) {
            return null;
        }

        $number = str_replace(',', '', $m[0]);

        if (! is_numeric($number)) {
            return null;
        }

        // A string, not a float: the column is a decimal and floats lose
        // precision on large rates.
        return number_format((float) $number, 2, '.', '');
    }

    protected function boolish(string $value, bool $default): bool
    {
        if ($value === '') {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'y', 'yes', 'true', 'active', 'live', 'published', 'on'], true);
    }

    /** Case- and spacing-insensitive key for matching names and codes. */
    protected function key(?string $value): string
    {
        return preg_replace('/\s+/', ' ', mb_strtolower(trim((string) $value))) ?? '';
    }
}
