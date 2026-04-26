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
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
        }

        .auth-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background:
                radial-gradient(circle at top, rgba(37, 99, 235, 0.08), transparent 38%),
                linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
        }

        .auth-panel {
            width: 100%;
            max-width: 460px;
        }

        .auth-card {
            border-radius: 20px;
            background: #ffffff;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
            border: 1px solid rgba(148, 163, 184, 0.22);
        }

        .auth-card .card-body {
            padding: 28px;
        }

        @media (max-width: 575.98px) {
            .auth-shell {
                padding: 16px;
            }

            .auth-card .card-body {
                padding: 22px;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="auth-shell">
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
