<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PageView;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CatalogueController extends Controller
{
    public function home()
    {
        $featured = Cache::remember('home.featured', 300, fn () => Product::active()
            ->whereRaw('is_featured = true')->with('category')
            ->orderBy('sort_order')->limit(8)->get());

        $categories = Cache::remember('home.categories', 300, fn () => Category::whereRaw('is_active = true')
            ->withCount('activeProducts')->orderBy('sort_order')->get());

        $stats = Cache::remember('home.stats', 600, fn () => [
            'products'   => Product::active()->count(),
            'categories' => Category::whereRaw('is_active = true')->count(),
            'visitors'   => PageView::distinct('visitor_hash')->count('visitor_hash'),
            'years'      => max(1, now()->year - config('business.established')),
        ]);

        return view('home', compact('featured', 'categories', 'stats'));
    }

    public function index(Request $request)
    {
        $query = Product::active()->with('category')
            ->search($request->query('q'));

        if ($slug = $request->query('category')) {
            $query->whereHas('category', fn ($c) => $c->where('slug', $slug));
        }

        if ($brand = $request->query('brand')) {
            $query->where('brand', $brand);
        }

        $query = match ($request->query('sort')) {
            'name'    => $query->orderBy('name'),
            'popular' => $query->orderByDesc('views'),
            'newest'  => $query->orderByDesc('created_at'),
            default   => $query->orderBy('sort_order')->orderBy('name'),
        };

        $products = $query->paginate(24)->withQueryString();

        $brands = Cache::remember('filter.brands', 600, fn () => Product::active()
            ->whereNotNull('brand')->distinct()->orderBy('brand')->pluck('brand'));

        return view('catalog.index', [
            'products' => $products,
            'brands'   => $brands,
            'category' => $slug ? Category::where('slug', $slug)->first() : null,
        ]);
    }

    public function category(Category $category, Request $request)
    {
        abort_unless($category->is_active, 404);

        $products = $category->activeProducts()
            ->search($request->query('q'))
            ->orderBy('sort_order')->orderBy('name')
            ->paginate(24)->withQueryString();

        return view('catalog.category', compact('category', 'products'));
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        // Cheap, race-safe view counter.
        $product->incrementQuietly('views');

        $related = Product::active()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->limit(4)->get();

        return view('catalog.show', compact('product', 'related'));
    }
}
