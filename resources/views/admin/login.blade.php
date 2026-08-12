<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Sign In | {{ config('business.name') }}</title>
<meta name="robots" content="noindex,nofollow">
<link rel="icon" href="{{ asset('assets/img/favicon.svg') }}" type="image/svg+xml">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="center mb-3">
      <span class="logo-mark" style="margin:0 auto 14px">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="m21 8-9-5-9 5v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/></svg>
      </span>
      <h1 class="h2">{{ config('business.name') }}</h1>
      <p class="small muted mt-1">Sign in to manage products and enquiries</p>
    </div>

    @if ($errors->any())
      <div class="alert alert-err"><span>{{ $errors->first() }}</span></div>
    @endif

    <form method="POST" action="{{ route('login') }}">
      @csrf
      <div class="field mb-2">
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
      </div>
      <div class="field mb-2">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required autocomplete="current-password">
      </div>
      <label class="small muted flex gap-1 items-center mb-3" style="cursor:pointer">
        <input type="checkbox" name="remember" value="1" style="width:auto"> Keep me signed in
      </label>
      <button class="btn btn-primary btn-lg btn-block" type="submit">Sign In</button>
    </form>

    <p class="tiny muted center mt-3">
      <a href="{{ route('home') }}" style="color:var(--navy-700);font-weight:600">← Back to site</a>
    </p>
  </div>
</div>
</body>
</html>
