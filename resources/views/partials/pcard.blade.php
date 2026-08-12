<article class="pcard rv">
  <a href="{{ route('product', $product) }}" class="pcard-img" aria-label="{{ $product->name }}">
    @if ($product->badge)
      <span class="pcard-badge tag {{ $product->badge === 'Bestseller' ? 'tag-saffron' : 'tag-green' }}">{{ $product->badge }}</span>
    @endif
    <img src="{{ $product->image_url }}" alt="{{ $product->name }}{{ $product->brand ? ' — '.$product->brand : '' }}"
         loading="lazy" decoding="async" width="220" height="220">
  </a>
  <div class="pcard-body">
    @if ($product->brand)<span class="pcard-brand">{{ $product->brand }}</span>@endif
    <a href="{{ route('product', $product) }}"><span class="pcard-name">{{ $product->name }}</span></a>
    <span class="pcard-meta">MOQ {{ $product->min_order_qty ?: '—' }} · per {{ $product->unit }}</span>
    <div class="pcard-foot">
      <a class="btn btn-outline btn-sm" href="{{ route('enquiry.create', ['product' => $product->slug]) }}">Get Quote</a>
    </div>
  </div>
</article>
