<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\TrackPageView;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Vercel's filesystem is read-only outside /tmp, and /tmp is wiped between
// invocations. Point Laravel's writable paths (logs, compiled views, cache)
// there and make sure the directories exist before anything tries to write.
// LARAVEL_STORAGE_PATH is read natively by Application::storagePath().
if (env('VERCEL')) {
    $storagePath = '/tmp/storage';

    foreach (['app/public', 'framework/cache/data', 'framework/sessions', 'framework/testing', 'framework/views', 'logs'] as $dir) {
        @mkdir("{$storagePath}/{$dir}", 0775, recursive: true);
    }

    $_ENV['LARAVEL_STORAGE_PATH'] = $storagePath;

    // bootstrap/cache is also read-only on Vercel; Laravel writes the
    // packages/services/config/routes manifests there, so move those too.
    @mkdir('/tmp/bootstrap-cache', 0775, recursive: true);
    $_ENV['APP_PACKAGES_CACHE'] = '/tmp/bootstrap-cache/packages.php';
    $_ENV['APP_SERVICES_CACHE'] = '/tmp/bootstrap-cache/services.php';
    $_ENV['APP_CONFIG_CACHE'] = '/tmp/bootstrap-cache/config.php';
    $_ENV['APP_ROUTES_CACHE'] = '/tmp/bootstrap-cache/routes-v7.php';
    $_ENV['APP_EVENTS_CACHE'] = '/tmp/bootstrap-cache/events.php';
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Every public page view is recorded for the per-page visitor counters.
        $middleware->appendToGroup('web', TrackPageView::class);
        $middleware->alias([
            'admin' => EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
