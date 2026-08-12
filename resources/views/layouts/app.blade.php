@php $biz = config('business'); @endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', $biz['name'].' — Electricals & Hardware Wholesaler, Nathdwara')</title>
<meta name="description" content="@yield('meta', 'Modi And Sons, Nathdwara — wholesaler, distributor and supplier of PVC pipes, electrical wires, fans, LED lights, water pumps, tarpaulin and fencing wire. Request a wholesale quote.')">
<link rel="canonical" href="{{ url()->current() }}">
<meta name="robots" content="@yield('robots', 'index,follow,max-image-preview:large')">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $biz['name'] }}">
<meta property="og:title" content="@yield('title', $biz['name'])">
<meta property="og:description" content="@yield('meta', $biz['tagline'])">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:locale" content="en_IN">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#102a4c">
<link rel="icon" href="{{ asset('assets/img/favicon.svg') }}" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">

<script type="application/ld+json">
{!! json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'HardwareStore',
  'name' => $biz['legal_name'],
  'description' => 'Wholesaler, distributor and supplier of electricals and hardware — PVC pipes, wires and cables, fans, LED lighting, water pumps, tarpaulin and fencing wire.',
  'url' => url('/'),
  'telephone' => $biz['phone'],
  'email' => $biz['email'],
  'foundingDate' => (string) $biz['established'],
  'currenciesAccepted' => 'INR',
  'address' => [
    '@type' => 'PostalAddress',
    'streetAddress' => $biz['address']['line1'],
    'addressLocality' => $biz['address']['city'],
    'addressRegion' => $biz['address']['state'],
    'postalCode' => $biz['address']['pincode'],
    'addressCountry' => 'IN',
  ],
  'areaServed' => $biz['serves'],
  'openingHours' => 'Mo-Sa 09:30-20:00',
], JSON_UNESCAPED_SLASHES) !!}
</script>
@stack('schema')

@if ($biz['ga4'])
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $biz['ga4'] }}"></script>
<script>
  window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}
  gtag('js',new Date());gtag('config','{{ $biz['ga4'] }}');
</script>
@endif
</head>
<body>

<div class="topbar">
  <div class="wrap">
    <div class="badges">
      <span><x-icon name="shield" :size="13"/> Wholesaler · Distributor · Supplier · Retailer</span>
      <span><x-icon name="pin" :size="13"/> {{ $biz['address']['city'] }}, {{ $biz['address']['state'] }}</span>
      <span><x-icon name="clock" :size="13"/> {{ $biz['hours'] }}</span>
    </div>
    <div class="flex gap-2 items-center">
      <a href="tel:{{ $biz['phone'] }}">{{ $biz['phone'] }}</a>
      <a href="https://wa.me/{{ $biz['whatsapp'] }}" target="_blank" rel="noopener">WhatsApp</a>
    </div>
  </div>
</div>

<header class="hdr" id="hdr">
  <div class="wrap hdr-main">
    <a href="{{ route('home') }}" class="logo" aria-label="{{ $biz['name'] }} — home">
      <span class="logo-mark">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="m21 8-9-5-9 5v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/>
        </svg>
      </span>
      <span><b>{{ $biz['name'] }}</b><small>Electricals &amp; Hardware</small></span>
    </a>

    <form class="searchbar" action="{{ route('products') }}" method="get" role="search">
      <label for="q" class="hp">Search products</label>
      <input id="q" type="search" name="q" value="{{ request('q') }}"
             placeholder="Search pipes, wires, fans, LED lights, pumps…" autocomplete="off">
      <button type="submit" aria-label="Search"><x-icon name="search" :size="16"/></button>
    </form>

    <div class="hdr-actions">
      <a class="btn btn-accent btn-sm" href="{{ route('enquiry.create') }}">
        <x-icon name="quote" :size="15"/> Request Quote
      </a>
      <button class="burger" id="burger" aria-label="Menu" aria-expanded="false"><i></i><i></i><i></i></button>
    </div>
  </div>

  <nav class="catbar" aria-label="Categories">
    <div class="wrap catbar-in">
      <a href="{{ route('products') }}" class="{{ request()->routeIs('products') ? 'on' : '' }}">All Products</a>
      @foreach ($navCategories as $c)
        <a href="{{ route('category', $c) }}" class="{{ request()->is('category/'.$c->slug) ? 'on' : '' }}">{{ $c->name }}</a>
      @endforeach
    </div>
  </nav>

  <nav class="mobnav" id="mobnav" aria-label="Mobile">
    <div class="wrap">
      <a href="{{ route('products') }}">All Products</a>
      @foreach ($navCategories as $c)
        <a href="{{ route('category', $c) }}">{{ $c->name }}</a>
      @endforeach
      <a href="{{ route('about') }}">About Us</a>
      <a href="{{ route('contact') }}">Contact</a>
      <a href="{{ route('enquiry.create') }}">Request a Quote</a>
    </div>
  </nav>
</header>

<main>
@if (session('ok'))
  <div class="wrap" style="padding-top:14px">
    <div class="alert alert-ok"><x-icon name="check"/> <span>{{ session('ok') }}</span></div>
  </div>
@endif
@yield('content')
</main>

<footer class="ftr">
  <div class="wrap">
    <div class="ftr-grid">
      <div>
        <a href="{{ route('home') }}" class="logo mb-2">
          <span class="logo-mark">
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="m21 8-9-5-9 5v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/>
            </svg>
          </span>
          <span><b>{{ $biz['name'] }}</b><small>Since {{ $biz['established'] }}</small></span>
        </a>
        <p>{{ $biz['tagline'] }}. Serving builders, contractors, retailers and farmers across
           {{ $biz['address']['district'] }} district and Rajasthan.</p>
        <p class="mt-2" style="color:#f59e0b;font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase">
          GST {{ $biz['gst'] }}</p>
      </div>
      <div>
        <h5>Categories</h5>
        @foreach ($navCategories->take(6) as $c)
          <a href="{{ route('category', $c) }}">{{ $c->name }}</a>
        @endforeach
      </div>
      <div>
        <h5>Company</h5>
        <a href="{{ route('about') }}">About Us</a>
        <a href="{{ route('products') }}">All Products</a>
        <a href="{{ route('enquiry.create') }}">Request a Quote</a>
        <a href="{{ route('contact') }}">Contact</a>
        <a href="{{ route('login') }}">Admin Login</a>
      </div>
      <div>
        <h5>Get In Touch</h5>
        <a href="tel:{{ $biz['phone'] }}">{{ $biz['phone'] }}</a>
        <a href="mailto:{{ $biz['email'] }}">{{ $biz['email'] }}</a>
        <a href="https://wa.me/{{ $biz['whatsapp'] }}" target="_blank" rel="noopener">Chat on WhatsApp</a>
        <p style="margin-top:10px">{{ $biz['address']['line1'] }}, {{ $biz['address']['city'] }},<br>
          {{ $biz['address']['district'] }}, {{ $biz['address']['state'] }} {{ $biz['address']['pincode'] }}</p>
      </div>
    </div>
    <div class="ftr-bot">
      <span>© {{ date('Y') }} {{ $biz['legal_name'] }}. All rights reserved.</span>
      <span>{{ implode(' · ', $biz['nature']) }}</span>
    </div>
  </div>
</footer>

<div class="fabs" id="fabs">
  <a class="fab fab-wa" href="https://wa.me/{{ $biz['whatsapp'] }}?text={{ urlencode('Hello Modi And Sons, I would like a wholesale quote.') }}"
     target="_blank" rel="noopener" aria-label="WhatsApp">
    <svg viewBox="0 0 24 24" fill="#fff"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.4-1.3l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-5.8c-.3-.1-1.7-.9-2-1-.3-.1-.5-.1-.7.1l-.9 1.2c-.2.2-.3.2-.6.1a8 8 0 0 1-4-3.4c-.2-.3 0-.5.1-.6l.5-.6.3-.5v-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4a3.4 3.4 0 0 0-1 2.4 5.8 5.8 0 0 0 1.2 3.1 13.4 13.4 0 0 0 5.1 4.5c.7.3 1.3.5 1.7.6a4 4 0 0 0 1.8.1c.6-.1 1.7-.7 2-1.4.2-.7.2-1.2.2-1.4Z"/></svg>
  </a>
  <a class="fab fab-call" href="tel:{{ $biz['phone'] }}" aria-label="Call">
    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round">
      <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/>
    </svg>
  </a>
</div>

<script src="{{ asset('assets/js/app.js') }}" defer></script>
@stack('scripts')
</body>
</html>
