<?php

namespace App\Support\Import;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Writes reviewed rows to the catalogue.
 *
 * Runs inside one transaction: a half-imported catalogue is worse than a failed
 * import, because there is no obvious way to tell which half landed.
 */
class CatalogueImporter
{
    /**
     * @param  array<int, array<string, mixed>>  $rows  rows from CatalogueMapper
     * @param  array<int, int>  $accept  line numbers the admin ticked
     * @return array{created: int, updated: int, skipped: int, categories: array<int, string>}
     */
    public function import(array $rows, array $accept, bool $createCategories): array
    {
        $accept = array_flip($accept);
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $newCategories = [];

        DB::transaction(function () use ($rows, $accept, $createCategories, &$created, &$updated, &$skipped, &$newCategories) {
            // Resolved inside the transaction so categories created for an early
            // row are reused by later ones instead of being created twice.
            $categories = Category::all()->keyBy(fn ($c) => Str::slug($c->name));

            foreach ($rows as $row) {
                if (! isset($accept[$row['line']]) || $row['action'] === 'error') {
                    $skipped++;

                    continue;
                }

                $categoryId = $row['category_id'];

                if ($categoryId === null) {
                    $slug = Str::slug($row['category']);

                    if ($categories->has($slug)) {
                        $categoryId = $categories->get($slug)->id;
                    } elseif ($createCategories && $slug !== '') {
                        $category = Category::create([
                            'name' => $row['category'],
                            'sort_order' => $categories->count(),
                            'is_active' => true,
                        ]);
                        $categories->put($slug, $category);
                        $newCategories[] = $category->name;
                        $categoryId = $category->id;
                    } else {
                        $skipped++;

                        continue;
                    }
                }

                $data = $row['data'] + ['category_id' => $categoryId];

                // Blank cells must not wipe values already set on an existing
                // product — an import of partial columns is a common way to
                // update just a price list or a set of codes.
                if ($row['existing_id']) {
                    $product = Product::find($row['existing_id']);

                    if (! $product) {
                        $skipped++;

                        continue;
                    }

                    $product->fill(array_filter(
                        $data,
                        fn ($v, $k) => ! in_array($k, ['sku', 'brand', 'short_description', 'description', 'min_order_qty', 'badge', 'specs', 'price', 'image_source'], true) || filled($v),
                        ARRAY_FILTER_USE_BOTH
                    ));
                    $product->save();
                    $updated++;

                    continue;
                }

                Product::create($data);
                $created++;
            }
        });

        $this->bustCaches();

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'categories' => array_values(array_unique($newCategories)),
        ];
    }

    /** Keeps the nav, homepage and filter lists in step with the new catalogue. */
    protected function bustCaches(): void
    {
        foreach (['nav.categories', 'home.featured', 'home.categories', 'home.stats', 'filter.brands'] as $key) {
            Cache::forget($key);
        }
    }
}
