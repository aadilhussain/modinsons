<?php

namespace App\Providers;

use App\Models\Category;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Nav categories are needed on every page — cache to keep queries flat.
        View::composer('*', function ($view) {
            $view->with('navCategories', Cache::remember('nav.categories', 600, function () {
                return Category::where('is_active', true)
                    ->orderBy('sort_order')->orderBy('name')
                    ->withCount('activeProducts')
                    ->get();
            }));
        });
    }
}
