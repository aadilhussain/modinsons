@extends('layouts.app')
@section('title', 'Request a Wholesale Quote | '.config('business.name'))
@section('meta', 'Send your requirement to Modi And Sons, Nathdwara — we quote wholesale rates for pipes, wires, fans, lighting, pumps, tarpaulin and fencing wire the same working day.')
@section('robots', 'index,follow')

@section('content')
@php $biz = config('business'); @endphp
<div class="wrap">
  <nav class="crumbs"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Request a Quote</span></nav>
</div>

<section class="sec-sm" style="padding-top:0">
  <div class="wrap" style="display:grid;grid-template-columns:1.55fr 1fr;gap:clamp(20px,3vw,40px);align-items:start">
    <div>
      <span class="eyebrow">Bulk &amp; Wholesale Enquiry</span>
      <h1 class="h1 mb-1">Tell us what you need</h1>
      <p class="muted mb-3">Fill in your requirement and we'll reply with our best rate, current stock and delivery time.
        Nothing is charged for a quote.</p>

      @if ($errors->any())
        <div class="alert alert-err">
          <x-icon name="tag"/>
          <span>Please correct the highlighted fields and try again.</span>
        </div>
      @endif

      <form class="form-card" method="POST" action="{{ route('enquiry.store') }}">
        @csrf
        @if ($product)
          <input type="hidden" name="product_id" value="{{ $product->id }}">
          <div class="flex gap-2 items-center mb-3" style="background:var(--navy-50);border:1px solid var(--navy-100);border-radius:var(--r);padding:12px">
            <img src="{{ $product->image_url }}" alt="" width="52" height="52" style="object-fit:contain">
            <div style="flex:1;min-width:0">
              <div class="tiny muted">Enquiring about</div>
              <div class="strong" style="font-size:14px">{{ $product->name }}</div>
              <div class="tiny muted">SKU {{ $product->sku }} · MOQ {{ $product->min_order_qty ?: '—' }}</div>
            </div>
            <a class="btn btn-ghost btn-sm" href="{{ route('enquiry.create') }}">Change</a>
          </div>
        @endif

        {{-- honeypot --}}
        <div class="hp" aria-hidden="true">
          <label for="website">Leave this field empty</label>
          <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div class="fgrid">
          <div class="field full">
            <label>I am buying as <span class="req">*</span></label>
            <div class="radio-row">
              @foreach (['Wholesale','Distributor','Retailer','Contractor','Institutional','Individual'] as $i => $t)
                <input type="radio" id="bt{{ $i }}" name="buyer_type" value="{{ $t }}"
                       @checked(old('buyer_type', 'Wholesale') === $t)>
                <label for="bt{{ $i }}">{{ $t }}</label>
              @endforeach
            </div>
            @error('buyer_type')<div class="err-msg">{{ $message }}</div>@enderror
          </div>

          <div class="field @error('name') err @enderror">
            <label for="name">Your name <span class="req">*</span></label>
            <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name">
            @error('name')<div class="err-msg">{{ $message }}</div>@enderror
          </div>

          <div class="field">
            <label for="company">Firm / shop name</label>
            <input id="company" name="company" value="{{ old('company') }}" autocomplete="organization">
          </div>

          <div class="field @error('phone') err @enderror">
            <label for="phone">Mobile number <span class="req">*</span></label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required autocomplete="tel" placeholder="+91">
            @error('phone')<div class="err-msg">{{ $message }}</div>@enderror
          </div>

          <div class="field @error('email') err @enderror">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email">
            @error('email')<div class="err-msg">{{ $message }}</div>@enderror
          </div>

          <div class="field">
            <label for="city">City / town</label>
            <input id="city" name="city" value="{{ old('city') }}" autocomplete="address-level2">
          </div>

          <div class="field">
            <label for="quantity">Quantity required</label>
            <input id="quantity" name="quantity" value="{{ old('quantity') }}" placeholder="e.g. 500">
          </div>

          <div class="field full">
            <label for="unit">Unit</label>
            <select id="unit" name="unit">
              @foreach (['Piece','Metre','Coil','Kilogram','Set','Bundle','Square Feet','Other'] as $u)
                <option @selected(old('unit', $product?->unit) === $u)>{{ $u }}</option>
              @endforeach
            </select>
          </div>

          <div class="field full @error('message') err @enderror">
            <label for="message">Your requirement</label>
            <textarea id="message" name="message"
              placeholder="List the items, sizes, brands and quantities you need. e.g. 1.5 sq mm FR wire — 20 coils; 25mm UPVC pipe — 300 metre.">{{ old('message') }}</textarea>
            @error('message')<div class="err-msg">{{ $message }}</div>@enderror
            <div class="hint">The more detail you give, the faster and sharper our quote.</div>
          </div>
        </div>

        <button class="btn btn-accent btn-lg btn-block mt-3" type="submit">
          <x-icon name="quote" :size="17"/> Send Enquiry
        </button>
        <p class="tiny muted center mt-2">We reply within one working day. Your details are never shared.</p>
      </form>
    </div>

    <aside>
      <div class="form-card mb-2">
        <h4 class="h3 mb-2">Faster on WhatsApp</h4>
        <p class="small muted mb-2">Send a photo of your item list or a sample — quickest way to get a firm rate.</p>
        <a class="btn btn-wa btn-block" target="_blank" rel="noopener"
           href="https://wa.me/{{ $biz['whatsapp'] }}?text={{ urlencode('Hello Modi And Sons, I need a wholesale quote for:') }}">
          <x-icon name="wa" :size="17"/> Message on WhatsApp</a>
        <a class="btn btn-outline btn-block mt-2" href="tel:{{ $biz['phone'] }}">
          <x-icon name="phone" :size="17"/> {{ $biz['phone'] }}</a>
      </div>

      <div class="form-card mb-2">
        <h4 class="h3 mb-2">How quoting works</h4>
        @foreach ([
          'You send the item list with quantities.',
          'We check live stock and current landed cost.',
          'You get a written rate, MOQ and delivery time.',
          'Confirm by phone or WhatsApp — we despatch.',
        ] as $i => $step)
          <div class="flex gap-2 mb-2" style="align-items:flex-start">
            <span class="tag tag-blue" style="flex-shrink:0">{{ $i+1 }}</span>
            <span class="small">{{ $step }}</span>
          </div>
        @endforeach
      </div>

      <div class="form-card" style="background:var(--saffron-50);border-color:var(--saffron-100)">
        <h4 class="h3 mb-1">Why no prices online?</h4>
        <p class="small muted">Wholesale rates move with copper, PVC and brand schemes, and they change by quantity
          and buyer type. A live quote is always sharper than a stale published price.</p>
      </div>
    </aside>
  </div>
</section>
@endsection
