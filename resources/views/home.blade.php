@extends('layouts.app')
@section('title', config('business.name').' — PVC Pipes, Wires, Fans & LED Lights Wholesaler in Nathdwara')
@section('meta', 'Wholesaler, distributor and supplier of PVC pipes, electrical wires, ceiling and table fans, LED panel lights, water pumps, tarpaulin and barbed wire in Nathdwara, Rajasthan. Request a wholesale quote — bulk rates on enquiry.')

@section('content')
@php $biz = config('business'); @endphp

<section class="hero">
  <div class="wrap hero-in">
    <div>
      <div class="role-pills">
        @foreach ($biz['nature'] as $n)
          <span class="role-pill"><x-icon name="check" :size="13"/> {{ $n }}</span>
        @endforeach
      </div>
      <h1>Electricals &amp; hardware, <span class="hl">supplied at wholesale rates</span></h1>
      <p class="sub">PVC pipes, house wiring cable, fans, LED lighting, water pumps, tarpaulin and fencing wire —
        stocked in depth and supplied to retailers, contractors, builders and farmers across Rajasthan since {{ $biz['established'] }}.</p>
      <div class="hero-cta">
        <a class="btn btn-primary btn-lg" href="{{ route('products') }}"><x-icon name="grid" :size="17"/> Browse Catalogue</a>
        <a class="btn btn-outline btn-lg" href="{{ route('enquiry.create') }}"><x-icon name="quote" :size="17"/> Request Bulk Quote</a>
      </div>
      <div class="hero-stats">
        <div><b data-count="{{ $stats['products'] }}" data-suffix="+">0</b><span>Products</span></div>
        <div><b data-count="{{ $stats['categories'] }}">0</b><span>Categories</span></div>
        <div><b data-count="{{ $stats['years'] }}" data-suffix="+">0</b><span>Years</span></div>
        <div><b data-count="{{ max($stats['visitors'], 1) }}" data-suffix="+">0</b><span>Site Visitors</span></div>
      </div>
    </div>

    <div class="hero-card">
      <span class="eyebrow">Shop by category</span>
      <h4>What we stock</h4>
      <p class="small muted">Tap a category to see the full range.</p>
      <div class="mini-grid">
        @foreach ($categories->take(9) as $c)
          <a class="mini" href="{{ route('category', $c) }}">
            <img src="{{ asset('assets/img/'.$c->icon.'.svg') }}" alt="" loading="lazy" width="38" height="38">
            <span>{{ $c->name }}</span>
          </a>
        @endforeach
      </div>
    </div>
  </div>
</section>

{{-- trust strip --}}
<section class="sec-sm bg-2" style="border-bottom:1px solid var(--line)">
  <div class="wrap">
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px">
      @foreach ([
        ['shield','ISI-marked brands','Polycab, Havells, Supreme, Crompton, Syska, Kirloskar and more.'],
        ['box','Deep stock, ready to move','Full sizes and gauges held in godown — no waiting on indent.'],
        ['tag','True wholesale rates','Slab pricing by quantity. Best rates quoted against your enquiry.'],
        ['truck','Delivery across Rajasthan','Nathdwara, Rajsamand, Udaipur, Chittorgarh, Bhilwara and beyond.'],
      ] as [$ic,$t,$d])
        <div class="flex gap-2 rv" style="align-items:flex-start">
          <span style="width:38px;height:38px;border-radius:9px;background:var(--navy-100);display:grid;place-items:center;flex-shrink:0;color:var(--navy-700)">
            <x-icon :name="$ic" :size="19"/>
          </span>
          <div>
            <div class="strong" style="font-size:14px">{{ $t }}</div>
            <div class="small muted" style="margin-top:2px">{{ $d }}</div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- categories --}}
<section class="sec">
  <div class="wrap">
    <div class="sec-head">
      <div>
        <span class="eyebrow">Our Range</span>
        <h2 class="h2">Browse by category</h2>
        <p>Nine categories covering everything an electrical and hardware buyer needs in one place.</p>
      </div>
      <a class="btn btn-outline btn-sm" href="{{ route('products') }}">View all products <x-icon name="arrow" :size="15"/></a>
    </div>
    <div class="grid grid-cats">
      @foreach ($categories as $c)
        <a class="ccard rv" href="{{ route('category', $c) }}">
          <img src="{{ asset('assets/img/'.$c->icon.'.svg') }}" alt="" loading="lazy" width="52" height="52">
          <h4>{{ $c->name }}</h4>
          <p>{{ $c->tagline }}</p>
          <span class="cnt">{{ $c->active_products_count }} products</span>
        </a>
      @endforeach
    </div>
  </div>
</section>

{{-- featured --}}
@if ($featured->count())
<section class="sec bg-2">
  <div class="wrap">
    <div class="sec-head">
      <div>
        <span class="eyebrow">Fast Moving</span>
        <h2 class="h2">Most enquired products</h2>
        <p>Our highest-volume lines. Prices are quoted against your quantity — send an enquiry for today's rate.</p>
      </div>
    </div>
    <div class="grid grid-prod">
      @foreach ($featured as $product)
        @include('partials.pcard', ['product' => $product])
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- buyer types --}}
<section class="sec">
  <div class="wrap">
    <div class="sec-head center" style="justify-content:center;text-align:center">
      <div>
        <span class="eyebrow">Who We Supply</span>
        <h2 class="h2">Built for trade buyers</h2>
        <p style="margin-left:auto;margin-right:auto">Rates and terms differ by buyer type. Tell us which you are and we quote accordingly.</p>
      </div>
    </div>
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(230px,1fr))">
      @foreach ([
        ['Retailers &amp; Shops','Counter stock in mixed lots. Small MOQs, regular resupply, and credit terms for established accounts.'],
        ['Wholesalers &amp; Distributors','Full case and coil quantities at distributor slab rates, with transport arranged to your godown.'],
        ['Contractors &amp; Builders','Site-wise supply against BOQ. Bulk wiring, conduit, DBs and lighting delivered to site on schedule.'],
        ['Farmers &amp; Institutions','Irrigation pipe, submersible pump sets, tirpal and fencing wire in project quantities.'],
      ] as $i => [$t,$d])
        <div class="form-card rv" style="border-top:3px solid var(--saffron-400)">
          <div class="tag tag-blue mb-2">0{{ $i+1 }}</div>
          <h4 style="font-size:15.5px">{!! $t !!}</h4>
          <p class="small muted mt-1">{{ $d }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- CTA --}}
<section class="sec-sm" style="background:linear-gradient(140deg,var(--navy-900),var(--navy-700))">
  <div class="wrap" style="display:flex;justify-content:space-between;align-items:center;gap:22px;flex-wrap:wrap;padding-top:12px;padding-bottom:12px">
    <div>
      <h2 class="h2" style="color:#fff">Tell us what you need. We'll quote today.</h2>
      <p style="color:#c9d8ec;margin-top:7px;max-width:56ch">
        Send your item list with quantities — we reply with our best wholesale rate, current stock and delivery time.</p>
    </div>
    <div class="flex gap-1 wrapf">
      <a class="btn btn-accent btn-lg" href="{{ route('enquiry.create') }}"><x-icon name="quote" :size="17"/> Request a Quote</a>
      <a class="btn btn-wa btn-lg" href="https://wa.me/{{ $biz['whatsapp'] }}" target="_blank" rel="noopener">
        <x-icon name="wa" :size="17"/> WhatsApp Us</a>
    </div>
  </div>
</section>
@endsection
