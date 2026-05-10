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
            background: #061733;
            overflow-y: auto;
        }

        .auth-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background:
                radial-gradient(circle at top, rgba(34, 211, 238, 0.12), transparent 30%),
                radial-gradient(circle at 85% 12%, rgba(99, 102, 241, 0.18), transparent 28%),
                linear-gradient(180deg, #051226 0%, #071a37 52%, #0a2043 100%);
        }

        .auth-panel {
            width: 100%;
            max-width: 400px;
        }

        .auth-card {
            border-radius: 22px;
            background: linear-gradient(180deg, rgba(5, 16, 38, .96) 0%, rgba(5, 20, 48, .92) 100%);
            box-shadow: 0 24px 60px rgba(2, 10, 30, 0.28), inset 0 1px 0 rgba(255,255,255,.05);
            border: 1px solid rgba(86, 169, 255, 0.16);
        }

        .auth-card .card-body {
            padding: 24px;
        }

        @media (max-width: 575.98px) {
            .auth-shell {
                padding: 12px;
                align-items: center;
            }

            .auth-panel {
                max-width: 360px;
            }

            .auth-card {
                border-radius: 18px;
            }

            .auth-card .card-body {
                padding: 18px;
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
