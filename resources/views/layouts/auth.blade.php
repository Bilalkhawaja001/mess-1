<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Mess')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('branding/nodesky-theme.css') }}">
    <style>
        :root { --auth-navy: #071b3d; --auth-navy-deep: #041127; --auth-orange: #ff5a1f; --auth-text: #0f172a; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; font-family: 'Inter', sans-serif; color: var(--auth-text); background: #eef2f7; }
        .auth-shell { min-height: 100vh; display: grid; place-items: center; padding: 28px; }
        .auth-panel { width: 100%; max-width: 410px; }
        .auth-panel--split { position: relative;
            max-width: 1040px; min-height: 650px; display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(390px, .92fr);
            overflow: hidden; border: 1px solid rgba(15, 23, 42, .08); border-radius: 24px;
            background: #fff; box-shadow: 0 24px 70px rgba(15, 23, 42, .14);
        }
        .auth-visual { min-width: 0; display: grid; place-items: center; padding: 64px; background: #ffffff; }
        .auth-form-side { display: flex; align-items: center; padding: 52px; background: #fff; }
        .auth-form-side-inner { width: 100%; }
        .auth-card { border: 1px solid rgba(15, 23, 42, .08); border-radius: 20px; background: #fff; box-shadow: 0 20px 55px rgba(15, 23, 42, .12); }
        .auth-card .card-body { padding: 30px; }
        @media (max-width: 900px) {
            .auth-panel--split { max-width: 480px; min-height: auto; grid-template-columns: 1fr; }
            .auth-visual { padding: 32px 54px; }
            .auth-form-side { padding: 40px; }
        }
        @media (max-width: 575.98px) {
            .auth-shell { padding: 14px; }
            .auth-panel--split { border-radius: 20px; }
            .auth-visual { padding: 24px 46px; }
            .auth-form-side { padding: 30px 24px; }
            .auth-card { border-radius: 18px; }
            .auth-card .card-body { padding: 22px; }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="auth-shell">
    @hasSection('auth_visual')
        <main class="auth-panel auth-panel--split">
            <aside class="auth-visual" aria-label="Admin Mess brand">@yield('auth_visual')</aside>
            <section class="auth-form-side"><div class="auth-form-side-inner">@yield('auth_content')</div></section>
        </main>
    @else
        <main class="auth-panel">
            <div class="auth-card card border-0"><div class="card-body">@yield('auth_content')</div></div>
        </main>
    @endif
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
