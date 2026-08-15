<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

class PageController extends Controller
{
    public function about()
    {
        return view('about');
    }

    public function contact()
    {
        return view('contact');
    }

    /**
     * Served from a route rather than public/robots.txt so the Sitemap line
     * always points at the host actually being crawled — a hard-coded domain
     * in a static file sends crawlers to whichever domain was current when it
     * was written.
     */
    public function robots(): Response
    {
        /*
         * Filter, sort and search URLs are deliberately NOT disallowed here.
         * They carry noindex,follow (see App\Support\Seo) and a crawler has to
         * be able to fetch a page to read that — blocking them in robots.txt
         * would leave them eligible for indexing on the strength of internal
         * links alone, which is the opposite of what we want.
         */
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /login',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain']);
    }

    public function sitemap(): Response
    {
        $urls = [
            ['loc' => route('home'), 'pri' => '1.0', 'freq' => 'weekly'],
            ['loc' => route('products'), 'pri' => '0.9', 'freq' => 'weekly'],
            ['loc' => route('enquiry.create'), 'pri' => '0.8', 'freq' => 'monthly'],
            ['loc' => route('contact'), 'pri' => '0.7', 'freq' => 'monthly'],
            ['loc' => route('about'), 'pri' => '0.6', 'freq' => 'yearly'],
        ];

        foreach (Category::whereRaw('is_active = true')->get() as $c) {
            $urls[] = [
                'loc' => route('category', $c),
                'pri' => '0.8',
                'freq' => 'weekly',
                'mod' => $c->updated_at,
            ];
        }

        foreach (Product::active()->with('category')->get() as $p) {
            $urls[] = [
                'loc' => route('product', $p),
                'pri' => '0.7',
                'freq' => 'monthly',
                'mod' => $p->updated_at,
                // Only real photos are worth submitting to Google Images; the
                // shared fallback card on every product would just be noise.
                'img' => $p->hasPhoto() ? url($p->social_image_url) : null,
                'title' => $p->name,
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
             .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" '
             .'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n";

        foreach ($urls as $u) {
            $xml .= '<url><loc>'.e($u['loc']).'</loc>';

            if (! empty($u['mod'])) {
                $xml .= '<lastmod>'.$u['mod']->toAtomString().'</lastmod>';
            }

            $xml .= "<changefreq>{$u['freq']}</changefreq><priority>{$u['pri']}</priority>";

            if (! empty($u['img'])) {
                $xml .= '<image:image><image:loc>'.e($u['img']).'</image:loc>'
                     .'<image:title>'.e($u['title']).'</image:title></image:image>';
            }

            $xml .= "</url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
