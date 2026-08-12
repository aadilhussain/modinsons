@php $biz = config('business'); @endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Admin') | {{ $biz['name'] }}</title>
<meta name="robots" content="noindex,nofollow">
<link rel="icon" href="{{ asset('assets/img/favicon.svg') }}" type="image/svg+xml">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body>
<div class="adm">
  <aside class="adm-side">
    <a href="{{ route('home') }}" class="logo" target="_blank">
      <span class="logo-mark">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="m21 8-9-5-9 5v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/></svg>
      </span>
      <span><b>{{ $biz['name'] }}</b><small>Admin Panel</small></span>
    </a>
    <nav class="adm-nav">
      <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'on' : '' }}">
        <x-icon name="chart"/> Dashboard</a>
      <a href="{{ route('admin.products.index') }}" class="{{ request()->routeIs('admin.products.*') ? 'on' : '' }}">
        <x-icon name="box"/> Products</a>
      <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'on' : '' }}">
        <x-icon name="grid"/> Categories</a>
      <a href="{{ route('admin.enquiries.index') }}" class="{{ request()->routeIs('admin.enquiries.*') ? 'on' : '' }}">
        <x-icon name="inbox"/> Enquiries</a>
      <a href="{{ route('admin.settings.edit') }}" class="{{ request()->routeIs('admin.settings.*') ? 'on' : '' }}">
        <x-icon name="settings"/> Settings</a>
      <a href="{{ route('home') }}" target="_blank"><x-icon name="eye"/> View Site</a>
      <form method="POST" action="{{ route('logout') }}" style="margin-top:10px">
        @csrf
        <button type="submit" style="all:unset;display:flex;align-items:center;gap:11px;padding:11px 18px;
          color:#98abc6;font-size:13.5px;font-weight:600;cursor:pointer;width:100%;box-sizing:border-box">
          <x-icon name="logout"/> Sign Out</button>
      </form>
    </nav>
  </aside>

  <main class="adm-main">
    @if (session('ok'))
      <div class="alert alert-ok"><x-icon name="check"/> <span>{{ session('ok') }}</span></div>
    @endif
    @if ($errors->any())
      <div class="alert alert-err"><x-icon name="tag"/>
        <span>{{ $errors->first() }}</span></div>
    @endif
    @yield('content')
  </main>
</div>
<script src="{{ asset('assets/js/app.js') }}" defer></script>
</body>
</html>
