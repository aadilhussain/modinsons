<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\PageView;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $from  = $today->copy()->subDays(29);

        // ---- headline counters ----
        $kpi = [
            'views_today'    => PageView::whereDate('viewed_on', $today)->count(),
            'visitors_today' => PageView::whereDate('viewed_on', $today)->distinct('visitor_hash')->count('visitor_hash'),
            'views_total'    => PageView::count(),
            'visitors_total' => PageView::distinct('visitor_hash')->count('visitor_hash'),
            'enq_new'        => Enquiry::where('status', 'new')->count(),
            'enq_total'      => Enquiry::count(),
            'enq_month'      => Enquiry::where('created_at', '>=', $today->copy()->startOfMonth())->count(),
            'products'       => Product::active()->count(),
        ];

        // ---- 30 day series (views, visitors, enquiries) ----
        $viewRows = PageView::selectRaw('viewed_on as d, count(*) as views, count(distinct visitor_hash) as visitors')
            ->where('viewed_on', '>=', $from->toDateString())
            ->groupBy('viewed_on')->pluck('views', 'd');

        $visitorRows = PageView::selectRaw('viewed_on as d, count(distinct visitor_hash) as visitors')
            ->where('viewed_on', '>=', $from->toDateString())
            ->groupBy('viewed_on')->pluck('visitors', 'd');

        $enqRows = Enquiry::selectRaw('date(created_at) as d, count(*) as c')
            ->where('created_at', '>=', $from)
            ->groupBy('d')->pluck('c', 'd');

        $series = [];
        for ($i = 0; $i < 30; $i++) {
            $d = $from->copy()->addDays($i)->toDateString();
            $series[] = [
                'date'      => $d,
                'label'     => Carbon::parse($d)->format('d M'),
                'views'     => (int) ($viewRows[$d] ?? 0),
                'visitors'  => (int) ($visitorRows[$d] ?? 0),
                'enquiries' => (int) ($enqRows[$d] ?? 0),
            ];
        }

        // ---- per-page visitor counts ----
        $pages = PageView::selectRaw('path, title, count(*) as views, count(distinct visitor_hash) as visitors')
            ->groupBy('path', 'title')
            ->orderByDesc('views')->limit(15)->get();

        $devices = PageView::selectRaw('device, count(*) as c')->groupBy('device')->pluck('c', 'device');

        $referrers = PageView::selectRaw('referrer, count(*) as c')
            ->whereNotNull('referrer')->groupBy('referrer')
            ->orderByDesc('c')->limit(8)->get();

        $topProducts = Product::orderByDesc('views')->limit(8)->get();

        $mostEnquired = Product::with('enquiries')
            ->get()
            ->filter(fn ($product) => $product->enquiries->count() > 0)
            ->sortByDesc(fn ($product) => $product->enquiries->count())
            ->take(8)
            ->values();

        $byType = Enquiry::selectRaw('buyer_type, count(*) as c')->groupBy('buyer_type')->pluck('c', 'buyer_type');
        $byStatus = Enquiry::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $recent = Enquiry::with('product')->latest()->limit(8)->get();

        return view('admin.dashboard', compact(
            'kpi', 'series', 'pages', 'devices', 'referrers',
            'topProducts', 'mostEnquired', 'byType', 'byStatus', 'recent'
        ));
    }
}
