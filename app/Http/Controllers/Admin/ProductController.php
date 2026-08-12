<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('category')
            ->search($request->query('q'))
            ->when($request->query('category'), fn ($q, $c) => $q->where('category_id', $c))
            ->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('admin.products.index', [
            'products'   => $products,
            'categories' => Category::orderBy('name')->get(),
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
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $path;
        }

        $product->update($data);
        $this->bust();

        return redirect()->route('admin.products.index')->with('ok', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

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
            'min_order_qty'     => ['nullable', 'string', 'max:60'],
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

        return $request->file('image')->store('products', 'public');
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
