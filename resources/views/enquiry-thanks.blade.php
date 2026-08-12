@extends('layouts.app')
@section('title', 'Enquiry Received | '.config('business.name'))
@section('robots', 'noindex,nofollow')

@section('content')
@php $biz = config('business'); @endphp
<section class="sec">
  <div class="wrap" style="max-width:660px">
    <div class="form-card center">
      <span style="width:66px;height:66px;border-radius:50%;background:var(--green-100);color:var(--green-600);
                   display:grid;place-items:center;margin:0 auto 18px">
        <x-icon name="check" :size="32"/>
      </span>
      <h1 class="h1 mb-2">Enquiry received</h1>
      <p class="muted mb-3">Thank you{{ $enquiry->name ? ', '.$enquiry->name : '' }} — we have your requirement and
        will come back with a rate within one working day.</p>

      <div style="background:var(--bg-2);border:1px solid var(--line);border-radius:var(--r);padding:16px;text-align:left">
        <div class="flex between wrapf gap-2 small">
          <span class="muted">Reference</span><strong class="strong">{{ $enquiry->reference }}</strong>
        </div>
        @if ($enquiry->product)
          <div class="flex between wrapf gap-2 small mt-1">
            <span class="muted">Product</span><strong class="strong">{{ $enquiry->product->name }}</strong>
          </div>
        @endif
        <div class="flex between wrapf gap-2 small mt-1">
          <span class="muted">Buyer type</span><strong class="strong">{{ $enquiry->buyer_type }}</strong>
        </div>
        @if ($enquiry->quantity)
          <div class="flex between wrapf gap-2 small mt-1">
            <span class="muted">Quantity</span><strong class="strong">{{ $enquiry->quantity }} {{ $enquiry->unit }}</strong>
          </div>
        @endif
      </div>

      <p class="tiny muted mt-2">Please quote this reference when you call.</p>

      <div class="flex gap-1 wrapf mt-3" style="justify-content:center">
        <a class="btn btn-wa" target="_blank" rel="noopener"
           href="https://wa.me/{{ $biz['whatsapp'] }}?text={{ urlencode('Hello, following up on enquiry '.$enquiry->reference) }}">
          <x-icon name="wa" :size="16"/> Follow up on WhatsApp</a>
        <a class="btn btn-outline" href="{{ route('products') }}">Continue browsing</a>
      </div>
    </div>
  </div>
</section>
@endsection
