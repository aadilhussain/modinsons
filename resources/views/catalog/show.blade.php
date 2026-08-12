@extends('layouts.app')
@section('title', $product->name.($product->brand ? ' — '.$product->brand : '').' | Wholesale Price on Enquiry')
@section('meta', Str::limit($product->short_description ?: $product->description, 158))
@section('og_type', 'product')
@section('image', url($product->social_image_url))

@push('schema')
<script type="application/ld+json">
{!! json_encode(array_filter([
  '@context' => 'https://schema.org', '@type' => 'Product',
  'name' => $product->name,
  'description' => $product->short_description ?: Str::limit($product->description, 300),
  'sku' => $product->sku ?: null,
  'mpn' => $product->sku ?: null,
  'category' => $product->category->name,
  'image' => url($product->social_image_url),
  'url' => route('product', $product),
  'brand' => ['@type' => 'Brand', 'name' => $product->brand ?: config('business.name')],
  'offers' => [
    '@type' => 'Offer',
    'priceCurrency' => 'INR',
    'availability' => 'https://schema.org/InStock',
    'url' => route('product', $product),
    'availableAtOrFrom' => ['@id' => url('/').'#business'],
    'seller' => ['@id' => url('/').'#business'],
    // Rates are quoted per enquiry, so there is no public price to publish.
    // businessFunction marks this as a sale offer without asserting a figure.
    'businessFunction' => 'http://purl.org/goodrelations/v1#Sell',
  ],
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@include('partials.breadcrumb-schema', ['trail' => [
  'Home' => route('home'),
  'Products' => route('products'),
  $product->category->name => route('category', $product->category),
  $product->name => null,
]])
@endpush

@section('content')
@php $biz = config('business'); @endphp
<div class="wrap">
  <nav class="crumbs" aria-label="Breadcrumb">
    <a href="{{ route('home') }}">Home</a><span class="sep">/</span>
    <a href="{{ route('products') }}">Products</a><span class="sep">/</span>
    <a href="{{ route('category', $product->category) }}">{{ $product->category->name }}</a>
    <span class="sep">/</span><span>{{ $product->name }}</span>
  </nav>
</div>

<section class="sec-sm" style="padding-top:0">
  <div class="wrap pdp">
    <div>
      <div class="pdp-media">
        @if ($product->badge)
          <span class="pcard-badge tag {{ $product->badge === 'Bestseller' ? 'tag-saffron' : 'tag-green' }}"
                style="top:14px;left:14px">{{ $product->badge }}</span>
        @endif
        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" width="420" height="420" fetchpriority="high">
      </div>
      <div class="trust-strip">
        <div><x-icon name="check" :size="17"/> <span>ISI-marked, genuine brand stock</span></div>
        <div><x-icon name="check" :size="17"/> <span>Bulk slab rates by quantity</span></div>
        <div><x-icon name="check" :size="17"/> <span>Delivery across Rajasthan</span></div>
        <div><x-icon name="check" :size="17"/> <span>GST invoice provided</span></div>
      </div>
    </div>

    <div>
      @if ($product->brand)<span class="pcard-brand">{{ $product->brand }}</span>@endif
      <h1>{{ $product->name }}</h1>
      <div class="flex gap-2 wrapf small muted mt-1">
        <span>SKU <strong class="strong">{{ $product->sku }}</strong></span>
        <span>·</span>
        <span><a href="{{ route('category', $product->category) }}" style="color:var(--navy-700);font-weight:600">{{ $product->category->name }}</a></span>
      </div>

      @if ($product->short_description)
        <p class="mt-2">{{ $product->short_description }}</p>
      @endif

      <div class="ask-box">
        <span class="lbl">Wholesale Price</span>
        <div class="val">Available on Enquiry</div>
        <p>Rates depend on quantity, brand and current stock. Send your requirement and we quote the same working day.</p>
        <div class="flex gap-2 wrapf mt-2 small">
          <span><strong class="strong">MOQ:</strong> {{ $product->min_order_qty ?: 'Negotiable' }}</span>
          <span><strong class="strong">Unit:</strong> {{ $product->unit }}</span>
        </div>
      </div>

      <div class="pdp-actions">
        <a class="btn btn-accent btn-lg" href="{{ route('enquiry.create', ['product' => $product->slug]) }}">
          <x-icon name="quote" :size="17"/> Request Quote</a>
        <a class="btn btn-wa btn-lg" target="_blank" rel="noopener"
           href="https://wa.me/{{ $biz['whatsapp'] }}?text={{ urlencode('Hello Modi And Sons, please quote for: '.$product->name.' ('.$product->sku.')') }}">
          <x-icon name="wa" :size="17"/> WhatsApp</a>
        <a class="btn btn-outline btn-lg" href="tel:{{ $biz['phone'] }}"><x-icon name="phone" :size="17"/> Call</a>
      </div>

      @if ($product->specs)
        <h3 class="h3 mt-3 mb-2">Specifications</h3>
        <table class="spec-table">
          <tbody>
          @foreach ($product->specs as $k => $v)
            <tr><th scope="row">{{ $k }}</th><td>{{ $v }}</td></tr>
          @endforeach
          </tbody>
        </table>
      @endif

      @if ($product->description)
        <h3 class="h3 mt-3 mb-2">Product details</h3>
        <p class="muted">{{ $product->description }}</p>
      @endif
    </div>
  </div>
</section>

@if ($related->count())
<section class="sec bg-2">
  <div class="wrap">
    <div class="sec-head">
      <div>
        <span class="eyebrow">More in {{ $product->category->name }}</span>
        <h2 class="h2">Related products</h2>
      </div>
      <a class="btn btn-outline btn-sm" href="{{ route('category', $product->category) }}">
        View category <x-icon name="arrow" :size="15"/></a>
    </div>
    <div class="grid grid-prod">
      @foreach ($related as $product)
        @include('partials.pcard', ['product' => $product])
      @endforeach
    </div>
  </div>
</section>
@endif
@endsection
