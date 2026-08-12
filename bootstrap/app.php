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
