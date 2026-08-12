<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records one row per page view for the built-in visitor counters.
 *
 * Privacy: the IP is never stored. We store a salted one-way hash only, which is
 * enough to count unique visitors but cannot be reversed to an address.
 */
class TrackPageView
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldTrack($request, $response)) {
            return $response;
        }

        try {
            $hash = hash('sha256', $request->ip().'|'.$request->userAgent().'|'.config('app.key'));
            $path = '/'.ltrim($request->path(), '/');

            // De-duplicate: one view per visitor per path per 30 minutes.
            $guard = 'pv:'.substr($hash, 0, 16).':'.md5($path);

            if (! Cache::has($guard)) {
                Cache::put($guard, 1, now()->addMinutes(30));

                PageView::create([
                    'path' => $path,
                    'title' => $this->titleFor($path),
                    'visitor_hash' => $hash,
                    'referrer' => substr((string) $request->headers->get('referer'), 0, 255) ?: null,
                    'device' => $this->device($request->userAgent()),
                    'viewed_on' => now()->toDateString(),
                ]);
            }
        } catch (\Throwable $e) {
            // Analytics must never break the page.
            report($e);
        }

        return $response;
    }

    protected function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }
        if ($response->getStatusCode() !== 200) {
            return false;
        }
        if ($request->ajax() || $request->wantsJson()) {
            return false;
        }
        if ($request->is('admin', 'admin/*', 'login', 'logout', 'up', 'storage/*')) {
            return false;
        }

        // Skip obvious crawlers so the counters reflect real people.
        $ua = strtolower((string) $request->userAgent());
        foreach (['bot', 'crawl', 'spider', 'slurp', 'headless', 'lighthouse', 'preview'] as $needle) {
            if (str_contains($ua, $needle)) {
                return false;
            }
        }

        return true;
    }

    protected function device(?string $ua): string
    {
        $ua = strtolower((string) $ua);

        return match (true) {
            str_contains($ua, 'ipad') || str_contains($ua, 'tablet') => 'tablet',
            str_contains($ua, 'mobi') || str_contains($ua, 'android') => 'mobile',
            default => 'desktop',
        };
    }

    protected function titleFor(string $path): string
    {
        return match (true) {
            $path === '/' => 'Home',
            str_starts_with($path, '/products/') => 'Product Detail',
            str_starts_with($path, '/category/') => 'Category',
            $path === '/products' => 'All Products',
            $path === '/enquiry' => 'Bulk Enquiry',
            $path === '/about' => 'About Us',
            $path === '/contact' => 'Contact',
            default => ucfirst(trim($path, '/')) ?: 'Page',
        };
    }
}
