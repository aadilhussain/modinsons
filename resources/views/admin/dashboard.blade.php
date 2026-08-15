@extends('admin.layout')
@section('title', 'Dashboard')

@section('content')
@php
  $maxV = max(1, collect($series)->max('views'));
  $maxE = max(1, collect($series)->max('enquiries'));
  $w = 720; $h = 170; $n = max(1, count($series) - 1);
  $pt = function ($key, $max) use ($series, $w, $h, $n) {
      return collect($series)->map(function ($d, $i) use ($key, $max, $w, $h, $n) {
          return round($i * ($w / $n), 1).','.round($h - ($d[$key] / $max) * ($h - 12), 1);
      })->implode(' ');
  };
@endphp

<div class="adm-head">
  <div>
    <h1 class="h2">Dashboard</h1>
    <p class="small muted mt-1">Traffic, enquiries and product performance — last 30 days.</p>
  </div>
  <div class="flex gap-1 wrapf">
    <a class="btn btn-outline btn-sm" href="{{ route('admin.enquiries.export') }}"><x-icon name="download" :size="15"/> Export CSV</a>
    <a class="btn btn-primary btn-sm" href="{{ route('admin.products.create') }}"><x-icon name="plus" :size="15"/> Add Product</a>
  </div>
</div>

<div class="kpis">
  <div class="kpi">
    <div class="lbl"><x-icon name="eye" :size="14"/> Page views today</div>
    <div class="num">{{ number_format($kpi['views_today']) }}</div>
    <div class="sub">{{ number_format($kpi['views_total']) }} all time</div>
  </div>
  <div class="kpi">
    <div class="lbl"><x-icon name="users" :size="14"/> Visitors today</div>
    <div class="num">{{ number_format($kpi['visitors_today']) }}</div>
    <div class="sub">{{ number_format($kpi['visitors_total']) }} unique all time</div>
  </div>
  <div class="kpi">
    <div class="lbl"><x-icon name="inbox" :size="14"/> New enquiries</div>
    <div class="num">{{ number_format($kpi['enq_new']) }}</div>
    <div class="sub">{{ number_format($kpi['enq_month']) }} this month · {{ number_format($kpi['enq_total']) }} total</div>
  </div>
  <div class="kpi">
    <div class="lbl"><x-icon name="box" :size="14"/> Live products</div>
    <div class="num">{{ number_format($kpi['products']) }}</div>
    <div class="sub"><a href="{{ route('admin.products.index') }}" style="color:var(--navy-700);font-weight:600">Manage catalogue →</a></div>
  </div>
  <div class="kpi">
    <div class="lbl"><x-icon name="tag" :size="14"/> Low / out of stock</div>
    <div class="num" style="{{ $kpi['low_stock'] ? 'color:var(--red-600)' : '' }}">{{ number_format($kpi['low_stock']) }}</div>
    <div class="sub">Products at or below their alert threshold</div>
  </div>
</div>

<div class="panel">
  <div class="panel-head">
    <h3>Traffic &amp; enquiries — last 30 days</h3>
    <span class="tag tag-blue">{{ array_sum(array_column($series, 'views')) }} views</span>
  </div>
  <div class="panel-body">
    <svg class="chart" viewBox="0 0 {{ $w }} {{ $h + 20 }}" preserveAspectRatio="none" role="img"
         aria-label="Daily page views, visitors and enquiries over the last 30 days">
      <defs>
        <linearGradient id="gv" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0" stop-color="#2f74b5" stop-opacity=".28"/>
          <stop offset="1" stop-color="#2f74b5" stop-opacity="0"/>
        </linearGradient>
      </defs>
      @for ($g = 0; $g <= 3; $g++)
        <line x1="0" y1="{{ $g * ($h/3) }}" x2="{{ $w }}" y2="{{ $g * ($h/3) }}" stroke="#e2e8f0" stroke-width="1"/>
      @endfor
      <polygon fill="url(#gv)" points="0,{{ $h }} {{ $pt('views', $maxV) }} {{ $w }},{{ $h }}"/>
      <polyline fill="none" stroke="#245c94" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round"
                points="{{ $pt('views', $maxV) }}"/>
      <polyline fill="none" stroke="#7aa8d4" stroke-width="1.8" stroke-dasharray="4 3"
                points="{{ $pt('visitors', $maxV) }}"/>
      <polyline fill="none" stroke="#f59e0b" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round"
                points="{{ $pt('enquiries', $maxE) }}"/>
    </svg>
    <div class="chart-legend">
      <span><i style="background:#245c94"></i>Page views</span>
      <span><i style="background:#7aa8d4"></i>Unique visitors</span>
      <span><i style="background:#f59e0b"></i>Enquiries</span>
      <span class="faint">{{ $series[0]['label'] }} → {{ $series[count($series)-1]['label'] }}</span>
    </div>
  </div>
</div>

<div class="cols-2">
  <div class="panel">
    <div class="panel-head"><h3>Visitors by page</h3><span class="tiny muted">Views · unique</span></div>
    <div class="panel-body">
      @forelse ($pages as $p)
        @php $pct = round($p->views / max(1, $pages->max('views')) * 100); @endphp
        <div class="bar-row">
          <span class="nm" title="{{ $p->path }}">{{ $p->title ?: $p->path }}
            <span class="faint tiny">{{ $p->path }}</span></span>
          <span class="track"><span class="fill" style="width:{{ $pct }}%"></span></span>
          <span class="val">{{ number_format($p->views) }}<span class="faint tiny"> / {{ $p->visitors }}</span></span>
        </div>
      @empty
        <p class="small muted">No traffic recorded yet.</p>
      @endforelse
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h3>Most viewed products</h3></div>
    <div class="panel-body">
      @forelse ($topProducts as $p)
        @php $pct = round($p->views / max(1, $topProducts->max('views')) * 100); @endphp
        <div class="bar-row">
          <span class="nm">{{ $p->name }}</span>
          <span class="track"><span class="fill" style="width:{{ $pct }}%"></span></span>
          <span class="val">{{ number_format($p->views) }}</span>
        </div>
      @empty
        <p class="small muted">No product views yet.</p>
      @endforelse
    </div>
  </div>
</div>

<div class="cols-2">
  <div class="panel">
    <div class="panel-head"><h3>Enquiries by buyer type</h3></div>
    <div class="panel-body">
      @forelse ($byType as $type => $count)
        @php $pct = round($count / max(1, $byType->max()) * 100); @endphp
        <div class="bar-row">
          <span class="nm">{{ $type }}</span>
          <span class="track"><span class="fill" style="width:{{ $pct }}%;background:linear-gradient(90deg,#e07b0c,#f59e0b)"></span></span>
          <span class="val">{{ $count }}</span>
        </div>
      @empty
        <p class="small muted">No enquiries yet.</p>
      @endforelse

      <h4 class="small strong mt-3 mb-2">Pipeline status</h4>
      <div class="flex gap-1 wrapf">
        @foreach ($byStatus as $s => $c)
          <span class="tag tag-{{ ['new'=>'blue','contacted'=>'amber','quoted'=>'violet','won'=>'green'][$s] ?? 'slate' }}">
            {{ $s }} · {{ $c }}</span>
        @endforeach
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h3>Most enquired products</h3></div>
    <div class="panel-body">
      @forelse ($mostEnquired as $p)
        @php $pct = round($p->enquiries_count / max(1, $mostEnquired->max('enquiries_count')) * 100); @endphp
        <div class="bar-row">
          <span class="nm">{{ $p->name }}</span>
          <span class="track"><span class="fill" style="width:{{ $pct }}%;background:linear-gradient(90deg,#127a55,#2bb37c)"></span></span>
          <span class="val">{{ $p->enquiries_count }}</span>
        </div>
      @empty
        <p class="small muted">No product-linked enquiries yet.</p>
      @endforelse

      <h4 class="small strong mt-3 mb-2">Devices</h4>
      <div class="flex gap-1 wrapf">
        @foreach ($devices as $d => $c)
          <span class="tag tag-slate">{{ $d }} · {{ number_format($c) }}</span>
        @endforeach
      </div>

      @if ($referrers->count())
        <h4 class="small strong mt-3 mb-2">Top referrers</h4>
        @foreach ($referrers as $r)
          <div class="flex between gap-2 small" style="padding:5px 0;border-bottom:1px solid var(--line)">
            <span class="muted" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:74%">
              {{ Str::limit(parse_url($r->referrer, PHP_URL_HOST) ?: $r->referrer, 40) }}</span>
            <strong class="strong">{{ $r->c }}</strong>
          </div>
        @endforeach
      @endif
    </div>
  </div>
</div>

@if ($lowStockProducts->count())
<div class="panel">
  <div class="panel-head">
    <h3>Stock alerts</h3>
    <a class="btn btn-ghost btn-sm" href="{{ route('admin.products.index', ['sort' => 'newest']) }}">Manage products <x-icon name="arrow" :size="14"/></a>
  </div>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>Product</th><th>Category</th><th>Stock left</th><th>Alert threshold</th><th></th></tr></thead>
      <tbody>
      @foreach ($lowStockProducts as $p)
        <tr>
          <td><strong class="strong">{{ $p->name }}</strong><div class="tiny muted">{{ $p->sku }}</div></td>
          <td class="small">{{ $p->category?->name }}</td>
          <td><span class="tag {{ $p->is_out_of_stock ? 'tag-red' : 'tag-amber' }}">
            {{ $p->is_out_of_stock ? 'Out of stock' : $p->stock_qty.' left' }}</span></td>
          <td class="small">{{ $p->low_stock_threshold ?? \App\Models\Product::DEFAULT_LOW_STOCK_THRESHOLD }}</td>
          <td><a class="btn btn-outline btn-sm" href="{{ route('admin.products.edit', $p) }}"><x-icon name="edit" :size="14"/></a></td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

<div class="panel">
  <div class="panel-head">
    <h3>Latest enquiries</h3>
    <a class="btn btn-ghost btn-sm" href="{{ route('admin.enquiries.index') }}">View all <x-icon name="arrow" :size="14"/></a>
  </div>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>Ref</th><th>Customer</th><th>Type</th><th>Product</th><th>Qty</th><th>Status</th><th>When</th></tr></thead>
      <tbody>
      @forelse ($recent as $e)
        <tr>
          <td><code class="tiny">{{ $e->reference }}</code></td>
          <td><strong class="strong">{{ $e->name }}</strong>
            <div class="tiny muted">{{ $e->company ?: $e->city }} · {{ $e->phone }}</div></td>
          <td><span class="tag tag-slate">{{ $e->buyer_type }}</span></td>
          <td class="small">{{ $e->product?->name ?? '—' }}</td>
          <td class="small">{{ $e->quantity ? $e->quantity.' '.$e->unit : '—' }}</td>
          <td><span class="tag tag-{{ $e->status_colour }}">{{ $e->status }}</span></td>
          <td class="tiny muted">{{ $e->created_at->diffForHumans() }}</td>
        </tr>
      @empty
        <tr><td colspan="7" class="empty">No enquiries yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
