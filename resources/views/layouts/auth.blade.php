<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mess Billing')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('branding/nodesky-theme.css') }}">
    @stack('styles')
</head>
<body>
<div class="auth-shell">
    <aside class="auth-aside">
        <div>
            <div class="auth-brand">
                <img src="{{ asset('branding/nodesky_logo.svg') }}" alt="NodeSky logo">
            </div>
            <div class="section-kicker mb-3"><i class="bi bi-stars"></i> Premium Corporate Light</div>
            <h1 class="display-6 fw-bold mb-3">Mess Billing Portal</h1>
            <p class="mb-4 text-white-50">Modern operations interface for billing, attendance, finance workflows, and member services with enterprise-grade clarity.</p>
            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="p-3 rounded-4 border border-light border-opacity-25 bg-white bg-opacity-10 h-100">
                        <div class="fw-semibold mb-1">Operational Control</div>
                        <div class="small text-white-50">Manage cycles, collections, attendance, and member activities from one clean system.</div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-3 rounded-4 border border-light border-opacity-25 bg-white bg-opacity-10 h-100">
                        <div class="fw-semibold mb-1">Member Workflows</div>
                        <div class="small text-white-50">One clean access surface for sign-in, registration, recovery, and member account actions.</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="auth-powered">
            <div class="powered-by-logo powered-by-logo-lg">
                <img src="{{ asset('branding/nodesky_logo.svg') }}" alt="NodeSky logo">
            </div>
            <div class="powered-by-text">Powerd by "NodeSky(smc-Private)Limited"</div>
        </div>
    </aside>

    <main class="auth-panel">
        <div class="auth-card card border-0">
            <div class="card-body">
                @yield('auth_content')
            </div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
