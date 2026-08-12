@extends('layouts.app')
@section('title', 'About Us — Electricals & Hardware Wholesaler in Nathdwara | '.config('business.name'))
@section('meta', 'Modi And Sons has supplied electricals and hardware from Nathdwara since 2012 — wholesaler, distributor, supplier and retailer serving Rajsamand, Udaipur and across Rajasthan.')

@push('schema')
@include('partials.breadcrumb-schema', ['trail' => ['Home' => route('home'), 'About Us' => null]])
@endpush

@section('content')
@php $biz = config('business'); @endphp
<div class="wrap"><nav class="crumbs"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>About Us</span></nav></div>

<section class="sec-sm bg-2" style="border-top:1px solid var(--line);border-bottom:1px solid var(--line)">
  <div class="wrap">
    <span class="eyebrow">Since {{ $biz['established'] }}</span>
    <h1 class="h1">A hardware counter that grew into a distribution business</h1>
    <p class="muted mt-2" style="max-width:74ch">{{ $biz['legal_name'] }} supplies electrical and hardware material
      from {{ $biz['address']['city'] }} to retailers, contractors, builders and farmers across
      {{ $biz['address']['district'] }} district and the wider Rajasthan market.</p>
  </div>
</section>

<section class="sec">
  <div class="wrap" style="display:grid;grid-template-columns:1.4fr 1fr;gap:clamp(24px,4vw,52px);align-items:start">
    <div>
      <h2 class="h2 mb-2">What we do</h2>
      <p class="muted mb-2">We hold depth in the lines that move every day on a building site or in a village shop —
        PVC and UPVC pipe in all classes, house wiring cable, fans, LED lighting, submersible pump sets, tarpaulin
        and fencing wire. Because we stock rather than indent, an order placed in the morning usually leaves the
        same day.</p>
      <p class="muted mb-2">We work with established brands — Polycab, Havells, Finolex, Supreme, Astral, Prince,
        Crompton, Usha, Bajaj, Syska, Philips, Kirloskar, Texmo and Tata Wiron — and we sell genuine, ISI-marked
        stock with a GST invoice on every despatch.</p>
      <p class="muted">Rates are quoted against your enquiry rather than published, because wholesale pricing moves
        with commodity cost, brand schemes and the quantity you take. A live quote is always sharper than a stale
        published price.</p>

      <h2 class="h2 mt-4 mb-2">How we sell</h2>
      <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(210px,1fr))">
        @foreach ([
          ['Wholesale','Full case, coil and bundle quantities at slab rates for the trade.'],
          ['Distribution','Territory supply to shops and sub-dealers with regular resupply.'],
          ['Project Supply','BOQ-wise supply to contractors and builders, delivered to site.'],
          ['Retail','Over the counter at Nathdwara for individual buyers and small jobs.'],
        ] as [$t,$d])
          <div class="form-card rv" style="border-top:3px solid var(--saffron-400)">
            <h4 style="font-size:15px">{{ $t }}</h4>
            <p class="small muted mt-1">{{ $d }}</p>
          </div>
        @endforeach
      </div>
    </div>

    <aside>
      <div class="form-card mb-2">
        <h4 class="h3 mb-2">Business details</h4>
        @foreach ([
          ['Firm', $biz['legal_name']],
          ['Proprietor', $biz['owner']],
          ['Established', $biz['established']],
          ['Nature', implode(', ', $biz['nature'])],
          ['GST', $biz['gst']],
          ['Location', $biz['address']['city'].', '.$biz['address']['state']],
        ] as [$k,$v])
          <div class="flex between gap-2 small" style="padding:9px 0;border-bottom:1px solid var(--line)">
            <span class="muted">{{ $k }}</span><strong class="strong" style="text-align:right">{{ $v }}</strong>
          </div>
        @endforeach
      </div>
      <div class="form-card" style="background:var(--navy-50);border-color:var(--navy-100)">
        <h4 class="h3 mb-1">Areas we serve</h4>
        <p class="small muted">{{ implode(' · ', $biz['serves']) }}</p>
        <a class="btn btn-primary btn-block mt-2" href="{{ route('enquiry.create') }}">Request a Quote</a>
      </div>
    </aside>
  </div>
</section>
@endsection
