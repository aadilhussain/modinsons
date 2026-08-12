@extends('layouts.app')
@section('title', 'Page Not Found | '.config('business.name'))
@section('meta', 'That page has moved or no longer exists. Browse the catalogue or send us your requirement for a wholesale quote.')
@section('robots', 'noindex,follow')

@section('content')
<section class="sec">
  <div class="wrap" style="max-width:640px;text-align:center">
    <span class="eyebrow">Error 404</span>
    <h1 class="h1 mb-2">We couldn’t find that page</h1>
    <p class="muted mb-3">The link may be out of date, or the item may have been moved to a different
      category. The catalogue below is the quickest way to what you were after.</p>

    <div class="flex gap-2 items-center" style="justify-content:center;flex-wrap:wrap">
      <a class="btn btn-accent btn-lg" href="{{ route('products') }}">
        <x-icon name="box" :size="17"/> Browse all products</a>
      <a class="btn btn-outline btn-lg" href="{{ route('enquiry.create') }}">
        <x-icon name="quote" :size="17"/> Request a quote</a>
    </div>

    @if ($navCategories->isNotEmpty())
      <div class="mt-3 pt-3" style="border-top:1px solid var(--line)">
        <div class="tiny muted mb-2" style="text-transform:uppercase;letter-spacing:.09em;font-weight:700">
          Popular categories</div>
        <div class="flex gap-1 items-center" style="justify-content:center;flex-wrap:wrap">
          @foreach ($navCategories->take(8) as $c)
            <a class="tag tag-slate" style="text-decoration:none" href="{{ route('category', $c) }}">{{ $c->name }}</a>
          @endforeach
        </div>
      </div>
    @endif
  </div>
</section>
@endsection
