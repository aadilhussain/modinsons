@extends('layouts.app')
@section('title', ($category ? $category->name : 'All Products').' — Wholesale Catalogue | '.config('business.name'))
@section('meta', 'Browse the full wholesale catalogue of electricals and hardware at Modi And Sons, Nathdwara. Request a quote on any product — bulk rates on enquiry.')

@section('content')
<div class="wrap">
  <nav class="crumbs" aria-label="Breadcrumb">
    <a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>All Products</span>
  </nav>
</div>

<section class="sec-sm" style="padding-top:0">
  <div class="wrap cat-layout">
    <aside>
      <div class="filters">
        <h5>Categories</h5>
        <div class="flist">
          <a href="{{ route('products', request()->except(['category','page'])) }}" class="{{ request('category') ? '' : 'on' }}">
            All Products
          </a>
          @foreach ($navCategories as $c)
            <a href="{{ route('products', array_merge(request()->except('page'), ['category' => $c->slug])) }}"
               class="{{ request('category') === $c->slug ? 'on' : '' }}">
              {{ $c->name }} <span>{{ $c->active_products_count }}</span>
            </a>
          @endforeach
        </div>
      </div>

      @if ($brands->count())
      <div class="filters">
        <h5>Brand</h5>
        <div class="flist">
          <a href="{{ route('products', request()->except(['brand','page'])) }}" class="{{ request('brand') ? '' : 'on' }}">All Brands</a>
          @foreach ($brands as $b)
            <a href="{{ route('products', array_merge(request()->except('page'), ['brand' => $b])) }}"
               class="{{ request('brand') === $b ? 'on' : '' }}">{{ $b }}</a>
          @endforeach
        </div>
      </div>
      @endif

      <div class="filters" style="background:var(--navy-50);border-color:var(--navy-100)">
        <h5 style="color:var(--navy-800)">Need a bulk rate?</h5>
        <p class="small muted mb-2">Send your item list with quantities and we'll quote within the day.</p>
        <a class="btn btn-primary btn-sm btn-block" href="{{ route('enquiry.create') }}">Request a Quote</a>
      </div>
    </aside>

    <div>
      <div class="toolbar">
        <div class="small muted">
          <strong class="strong">{{ $products->total() }}</strong> products
          @if (request('q')) matching "<strong class="strong">{{ request('q') }}</strong>" @endif
        </div>
        <form method="get" class="flex gap-1 items-center">
          @foreach (request()->except(['sort','page']) as $k => $v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
          @endforeach
          <label for="sort" class="small muted">Sort</label>
          <select id="sort" name="sort" class="select" data-autosubmit>
            <option value="">Relevance</option>
            <option value="name" @selected(request('sort')==='name')>Name A–Z</option>
            <option value="popular" @selected(request('sort')==='popular')>Most viewed</option>
            <option value="newest" @selected(request('sort')==='newest')>Newest first</option>
          </select>
        </form>
      </div>

      @if ($products->count())
        <div class="grid grid-prod">
          @foreach ($products as $product)
            @include('partials.pcard', ['product' => $product])
          @endforeach
        </div>
        <div class="pager">{{ $products->links('partials.pager') }}</div>
      @else
        <div class="empty form-card">
          <x-icon name="box" :size="46"/>
          <h3 class="h3 mb-1">No products found</h3>
          <p class="small">Try a different search, or tell us what you need and we'll source it.</p>
          <a class="btn btn-primary mt-3" href="{{ route('enquiry.create') }}">Send an enquiry</a>
        </div>
      @endif
    </div>
  </div>
</section>
@endsection
