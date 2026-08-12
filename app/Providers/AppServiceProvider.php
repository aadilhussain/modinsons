<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Setting;
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

        $this->applyBusinessSettings();

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

    /**
     * Overlay the admin-editable settings on top of config/business.php.
     *
     * Deliberately uncached: the table holds a couple of dozen rows behind a
     * primary key, and the deploy target runs several isolated instances whose
     * caches cannot be invalidated together — a stale phone number on the live
     * site is worse than one small query per request.
     */
    protected function applyBusinessSettings(): void
    {
        try {
            $values = Setting::allValues();
        } catch (\Throwable $e) {
            // The table does not exist yet (fresh clone, mid-migration). The
            // config file defaults stand in until it does.
            return;
        }

        foreach ($values as $key => $value) {
            config(['business.'.$key => $value]);
        }
    }
}
