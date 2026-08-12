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

    public function sitemap(): Response
    {
        $urls = [
            ['loc' => route('home'), 'pri' => '1.0', 'freq' => 'weekly'],
            ['loc' => route('products'), 'pri' => '0.9', 'freq' => 'weekly'],
            ['loc' => route('about'), 'pri' => '0.6', 'freq' => 'yearly'],
            ['loc' => route('contact'), 'pri' => '0.7', 'freq' => 'yearly'],
            ['loc' => route('enquiry.create'), 'pri' => '0.8', 'freq' => 'monthly'],
        ];

        foreach (Category::where('is_active', true)->get() as $c) {
            $urls[] = ['loc' => route('category', $c), 'pri' => '0.8', 'freq' => 'weekly'];
        }

        foreach (Product::active()->get() as $p) {
            $urls[] = ['loc' => route('product', $p), 'pri' => '0.7', 'freq' => 'monthly'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
             .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $u) {
            $xml .= "<url><loc>{$u['loc']}</loc><changefreq>{$u['freq']}</changefreq><priority>{$u['pri']}</priority></url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
