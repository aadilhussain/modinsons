<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories', [
            'categories' => Category::withCount('products')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Category::create($this->validated($request));
        Cache::forget('nav.categories');

        return back()->with('ok', 'Category added.');
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validated($request));
        Cache::forget('nav.categories');

        return back()->with('ok', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['category' => 'Move or delete this category\'s products first.']);
        }

        $category->delete();
        Cache::forget('nav.categories');

        return back()->with('ok', 'Category deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'tagline'     => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon'        => ['nullable', 'string', 'max:40'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
