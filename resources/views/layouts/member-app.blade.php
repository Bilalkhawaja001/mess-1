<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Member App')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --app-bg:#f5f7fb; --app-card:#fff; --app-primary:#1f6feb; --app-text:#172033; --app-muted:#6b7280; }
        body { margin:0; background:var(--app-bg); color:var(--app-text); font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .member-app-shell { min-height:100vh; padding-bottom:88px; }
        .app-header { position:sticky; top:0; z-index:20; background:linear-gradient(135deg,#0f3d91,#1f6feb); color:#fff; padding:18px 18px 16px; border-radius:0 0 24px 24px; box-shadow:0 10px 28px rgba(31,111,235,.22); }
        .app-header small { opacity:.82; display:block; font-weight:600; }
        .app-header h1 { font-size:22px; margin:4px 0 0; font-weight:800; letter-spacing:-.02em; }
        .app-content { padding:16px; max-width:760px; margin:0 auto; }
        .app-card { background:var(--app-card); border:1px solid #e5eaf3; border-radius:20px; padding:16px; box-shadow:0 8px 24px rgba(15,23,42,.05); margin-bottom:14px; }
        .app-card h2 { font-size:18px; font-weight:800; margin:0 0 8px; }
        .app-card h3 { font-size:15px; font-weight:800; margin:0 0 6px; }
        .muted { color:var(--app-muted); }
        .metric-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
        .metric { background:#f8fbff; border:1px solid #e1ebfb; border-radius:16px; padding:14px; }
        .metric span { display:block; color:var(--app-muted); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
        .metric strong { display:block; margin-top:5px; font-size:20px; }
        .app-list { display:flex; flex-direction:column; gap:10px; }
        .app-list-item { border:1px solid #edf1f7; border-radius:16px; padding:12px; background:#fff; }
        .app-pill { display:inline-flex; align-items:center; border-radius:999px; padding:4px 9px; background:#eef5ff; color:#164ea6; font-size:12px; font-weight:800; }
        .app-icon { width:44px; height:44px; display:grid; place-items:center; border-radius:14px; background:#eef5ff; color:var(--app-primary); font-weight:900; }
        .bottom-nav { position:fixed; left:12px; right:12px; bottom:12px; z-index:30; max-width:760px; margin:0 auto; display:grid; grid-template-columns:repeat(5,1fr); gap:4px; background:#fff; border:1px solid #e5eaf3; border-radius:22px; padding:8px; box-shadow:0 10px 28px rgba(15,23,42,.14); }
        .bottom-nav a { text-decoration:none; color:#6b7280; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:3px; font-size:11px; font-weight:800; min-height:48px; border-radius:16px; }
        .bottom-nav a.is-active { color:#1f6feb; background:#eef5ff; }
        .bottom-nav i { font-size:18px; }
        @media (min-width: 768px) { .app-header { max-width:760px; margin:0 auto; } }
    </style>
    @stack('styles')
</head>
<body>
<div class="member-app-shell">
    <header class="app-header">
        <small>Mess Member App</small>
        <h1>@yield('app_title', 'Dashboard')</h1>
    </header>
    <main class="app-content">
        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if(session('warning')) <div class="alert alert-warning">{{ session('warning') }}</div> @endif
        @if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif
        @yield('content')
    </main>
</div>
<nav class="bottom-nav" aria-label="Member app navigation">
    <a class="{{ request()->routeIs('member.app.dashboard') ? 'is-active' : '' }}" href="{{ route('member.app.dashboard') }}"><i class="bi bi-grid-1x2"></i><span>Home</span></a>
    <a class="{{ request()->routeIs('member.app.payments.*') || request()->routeIs('member.app.bill') ? 'is-active' : '' }}" href="{{ route('member.app.payments.index') }}"><i class="bi bi-receipt"></i><span>Bill</span></a>
    <a class="{{ request()->routeIs('member.app.statement.*') ? 'is-active' : '' }}" href="{{ route('member.app.statement.index') }}"><i class="bi bi-journal-text"></i><span>Statement</span></a>
    <a class="{{ request()->routeIs('member.app.menu.*') ? 'is-active' : '' }}" href="{{ route('member.app.menu.index') }}"><i class="bi bi-cup-hot"></i><span>Menu</span></a>
    <a class="{{ request()->routeIs('member.app.profile.*') ? 'is-active' : '' }}" href="{{ route('member.app.profile.index') }}"><i class="bi bi-person"></i><span>Profile</span></a>
</nav>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
