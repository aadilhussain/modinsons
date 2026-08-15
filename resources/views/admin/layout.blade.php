@php $biz = config('business'); @endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Admin') | {{ $biz['name'] }}</title>
<meta name="robots" content="noindex,nofollow">
<meta name="csrf-token" content="{{ csrf_token() }}">
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
    <div class="adm-topbar">
      <div class="adm-clock" id="admClock" title="{{ config('app.timezone') }}">
        <x-icon name="clock" :size="15"/>
        <span id="admClockTime">--:--:--</span>
        <span class="adm-clock-date" id="admClockDate"></span>
      </div>
      <div class="adm-notif">
        <button type="button" class="adm-notif-btn" id="admNotifBtn" aria-haspopup="true" aria-expanded="false"
                data-seen-url="{{ route('admin.notifications.seen') }}">
          <x-icon name="bell" :size="17"/>
          @if ($unseenEnquiriesCount > 0)
            <span class="adm-notif-badge" id="admNotifBadge">{{ $unseenEnquiriesCount > 99 ? '99+' : $unseenEnquiriesCount }}</span>
          @endif
        </button>
        <div class="adm-notif-drop" id="admNotifDrop">
          <div class="adm-notif-head">
            <strong>New enquiries</strong>
            <span class="tiny muted">{{ $unseenEnquiriesCount }} unread</span>
          </div>
          @forelse ($recentNewEnquiries as $e)
            <a href="{{ route('admin.enquiries.index', ['status' => 'new']) }}" class="adm-notif-item">
              <span class="strong small">{{ $e->name }}</span>
              <span class="tiny muted">{{ $e->product?->name ?? 'General enquiry' }} · {{ $e->created_at->diffForHumans() }}</span>
            </a>
          @empty
            <p class="tiny muted" style="padding:16px">No new enquiries.</p>
          @endforelse
          <a href="{{ route('admin.enquiries.index', ['status' => 'new']) }}" class="adm-notif-foot">View all new enquiries →</a>
        </div>
      </div>
    </div>
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
