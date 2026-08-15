<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Support\Images\VercelBlobStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    /**
     * Download the catalogue as CSV.
     *
     * The columns are the ones the importer reads back, so an export can be
     * edited in a spreadsheet and imported again to bulk-update the catalogue.
     * Each distinct spec becomes its own column for the same reason.
     */
    public function export(): StreamedResponse
    {
        $specKeys = Product::query()->whereNotNull('specs')->pluck('specs')
            ->flatMap(fn ($s) => array_keys((array) $s))
            ->unique()->sort()->values()->all();

        $name = 'products-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($specKeys) {
            $out = fopen('php://output', 'w');

            // Excel reads a bare UTF-8 CSV as Windows-1252 and mangles the ₹ and
            // × characters; the BOM is what tells it otherwise.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, array_merge([
                'Name', 'SKU', 'Category', 'Brand', 'Short Description', 'Description',
                'Unit', 'Price', 'Min Order Qty', 'Stock Qty', 'Low Stock Threshold',
                'Badge', 'Sort Order', 'Is Active', 'Is Featured',
            ], $specKeys));

            Product::with('category')->orderBy('category_id')->orderBy('sort_order')->orderBy('name')
                ->chunk(200, function ($rows) use ($out, $specKeys) {
                    foreach ($rows as $p) {
                        $specs = (array) $p->specs;

                        fputcsv($out, array_merge([
                            $p->name, $p->sku, $p->category?->name, $p->brand,
                            $p->short_description, $p->description, $p->unit,
                            $p->price, $p->min_order_qty, $p->stock_qty, $p->low_stock_threshold,
                            $p->badge, $p->sort_order,
                            $p->is_active ? 'Yes' : 'No',
                            $p->is_featured ? 'Yes' : 'No',
                        ], array_map(fn ($k) => $specs[$k] ?? '', $specKeys)));
                    }
                });

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }

    /**
     * Sort options for the admin list, mapped to the ordering each applies.
     *
     * Whitelisted rather than taking a column from the query string, so the
     * sort parameter can never be used to order by an arbitrary column.
     */
    protected const SORTS = [
        'newest'     => ['label' => 'Newest first',   'apply' => ['created_at', 'desc']],
        'oldest'     => ['label' => 'Oldest first',   'apply' => ['created_at', 'asc']],
        'az'         => ['label' => 'Name A–Z',       'apply' => ['name', 'asc']],
        'za'         => ['label' => 'Name Z–A',       'apply' => ['name', 'desc']],
        'price_low'  => ['label' => 'Price low → high', 'apply' => ['price', 'asc']],
        'price_high' => ['label' => 'Price high → low', 'apply' => ['price', 'desc']],
        'sku'        => ['label' => 'Code / SKU',     'apply' => ['sku', 'asc']],
        'views'      => ['label' => 'Most viewed',    'apply' => ['views', 'desc']],
    ];

    public function index(Request $request)
    {
        $sort = $request->query('sort');
        $sort = isset(self::SORTS[$sort]) ? $sort : 'newest';
        [$column, $direction] = self::SORTS[$sort]['apply'];

        $query = Product::with('category')
            ->search($request->query('q'))
            ->when($request->query('category'), fn ($q, $c) => $q->where('category_id', $c));

        // Unpriced products always sit last rather than filling the first page
        // with blanks, whichever direction the rate is sorted in.
        if ($column === 'price') {
            $query->orderByRaw('CASE WHEN price IS NULL THEN 1 ELSE 0 END');
        }

        $products = $query->orderBy($column, $direction)
            ->orderBy('id', 'desc')
            ->paginate(20)->withQueryString();

        return view('admin.products.index', [
            'products'   => $products,
            'categories' => Category::orderBy('name')->get(),
            'sorts'      => collect(self::SORTS)->map->label,
            'sort'       => $sort,
        ]);
    }

    public function create()
    {
        return view('admin.products.form', [
            'product'    => new Product(['unit' => 'Piece', 'is_active' => true]),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['image_path'] = $this->handleImage($request);

        Product::create($data);
        $this->bust();

        return redirect()->route('admin.products.index')->with('ok', 'Product added.');
    }

    public function edit(Product $product)
    {
        return view('admin.products.form', [
            'product'    => $product,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product);

        if ($path = $this->handleImage($request)) {
            $this->deleteImage($product->image_path);
            $data['image_path'] = $path;
        }

        $product->update($data);
        $this->bust();

        return redirect()->route('admin.products.index')->with('ok', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $this->deleteImage($product->image_path);

        $product->delete();
        $this->bust();

        return back()->with('ok', 'Product deleted.');
    }

    public function toggle(Product $product)
    {
        $product->update(['is_active' => ! $product->is_active]);
        $this->bust();

        return back()->with('ok', 'Visibility updated.');
    }

    protected function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'category_id'       => ['required', 'exists:categories,id'],
            'name'              => ['required', 'string', 'max:180'],
            'brand'             => ['nullable', 'string', 'max:80'],
            'sku'               => ['nullable', 'string', 'max:60'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description'       => ['nullable', 'string', 'max:5000'],
            'unit'              => ['required', 'string', 'max:40'],
            'price'             => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'min_order_qty'     => ['nullable', 'string', 'max:60'],
            'stock_qty'         => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'badge'             => ['nullable', 'string', 'max:30'],
            'sort_order'        => ['nullable', 'integer', 'min:0'],
            'spec_key'          => ['nullable', 'array'],
            'spec_key.*'        => ['nullable', 'string', 'max:60'],
            'spec_val'          => ['nullable', 'array'],
            'spec_val.*'        => ['nullable', 'string', 'max:160'],
            'image'             => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        // Fold the paired spec inputs into a clean key => value array.
        $specs = [];
        foreach ($request->input('spec_key', []) as $i => $k) {
            $v = $request->input("spec_val.$i");
            if (filled($k) && filled($v)) {
                $specs[trim($k)] = trim($v);
            }
        }

        $data['specs'] = $specs ?: null;
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        unset($data['spec_key'], $data['spec_val'], $data['image']);

        return $data;
    }

    protected function handleImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $file = $request->file('image');

        // On Vercel local disk storage doesn't survive past the request (see
        // Product::isCommittedAsset() doc comment), so uploads go to Vercel
        // Blob whenever it's configured. Falls back to the local public disk
        // for environments without a blob token (e.g. plain local/shared hosting).
        if (VercelBlobStorage::isConfigured()) {
            return VercelBlobStorage::put($file, 'products');
        }

        // On Vercel that fallback disk never persists, so a silent write there
        // would look like a successful upload and then quietly 404 later.
        // Fail loudly here instead so a missing/misconfigured blob token is
        // obvious immediately, not discovered after the fact on the live page.
        if (env('VERCEL')) {
            throw ValidationException::withMessages([
                'image' => 'Image uploads aren\'t configured yet — set BLOB_READ_WRITE_TOKEN in the '
                    .'Vercel project\'s environment variables (Production scope) and redeploy, then try again.',
            ]);
        }

        return $file->store('products', 'public');
    }

    /** Deletes a product photo from wherever handleImage() put it. */
    protected function deleteImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            VercelBlobStorage::delete($path);
        } else {
            Storage::disk('public')->delete($path);
        }
    }

    protected function bust(): void
    {
        Cache::forget('home.featured');
        Cache::forget('home.categories');
        Cache::forget('home.stats');
        Cache::forget('nav.categories');
        Cache::forget('filter.brands');
    }
}
