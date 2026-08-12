@extends('layouts.app')
@section('title', $category->name.' — Wholesale Supplier in Nathdwara | '.config('business.name'))
@section('meta', Str::limit($category->description ?: $category->tagline, 160))

@push('schema')
<script type="application/ld+json">
{!! json_encode([
  '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
  'itemListElement' => [
    ['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>route('home')],
    ['@type'=>'ListItem','position'=>2,'name'=>'Products','item'=>route('products')],
    ['@type'=>'ListItem','position'=>3,'name'=>$category->name,'item'=>route('category',$category)],
  ],
], JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="wrap">
  <nav class="crumbs" aria-label="Breadcrumb">
    <a href="{{ route('home') }}">Home</a><span class="sep">/</span>
    <a href="{{ route('products') }}">Products</a><span class="sep">/</span><span>{{ $category->name }}</span>
  </nav>
</div>

<section class="sec-sm bg-2" style="border-top:1px solid var(--line);border-bottom:1px solid var(--line)">
  <div class="wrap flex gap-3 items-center wrapf">
    <img src="{{ asset('assets/img/'.$category->icon.'.svg') }}" alt="" width="64" height="64" style="flex-shrink:0">
    <div style="flex:1;min-width:240px">
      <span class="eyebrow">{{ $category->tagline }}</span>
      <h1 class="h1">{{ $category->name }}</h1>
      @if ($category->description)<p class="muted mt-1" style="max-width:70ch">{{ $category->description }}</p>@endif
    </div>
    <a class="btn btn-accent" href="{{ route('enquiry.create') }}"><x-icon name="quote" :size="16"/> Bulk Enquiry</a>
  </div>
</section>

<section class="sec-sm">
  <div class="wrap">
    <div class="toolbar">
      <div class="small muted"><strong class="strong">{{ $products->total() }}</strong> products in {{ $category->name }}</div>
      <a class="btn btn-ghost btn-sm" href="{{ route('products') }}">View all categories</a>
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
        <p>No products listed in this category yet.</p>
        <a class="btn btn-primary mt-3" href="{{ route('enquiry.create') }}">Ask us what's available</a>
      </div>
    @endif
  </div>
</section>
@endsection
