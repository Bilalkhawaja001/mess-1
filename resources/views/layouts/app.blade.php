<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mess Billing Enterprise Panel')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root{--bg:#f3f6fb;--surface:#fff;--text:#0f172a;--muted:#64748b;--border:#e2e8f0;--primary:#2563eb;--radius:14px;--shadow:0 8px 24px rgba(15,23,42,.06)}
        *{box-sizing:border-box}
        body{margin:0;background:var(--bg);color:var(--text);font-family:'Inter',system-ui,sans-serif}
        .app-shell{min-height:100vh;display:flex}
        .sidebar{width:272px;background:var(--surface);border-right:1px solid var(--border);padding:14px}
        .content-wrap{flex:1;display:flex;flex-direction:column;min-width:0}
        .topbar{background:#fff;border-bottom:1px solid var(--border);padding:12px 18px;display:flex;justify-content:space-between;align-items:center;gap:12px}
        .page-body{padding:16px 18px 18px}

        .sb-brand{display:flex;gap:10px;align-items:center;padding:10px;border:1px solid var(--border);border-radius:12px;background:#f8fafc}
        .sb-brand .logo{width:34px;height:34px;border-radius:10px;background:#dbeafe;color:#1d4ed8;display:grid;place-items:center;font-weight:800}
        .sb-title{font-size:13px;font-weight:800;line-height:1.2}
        .sb-sub{font-size:11px;color:var(--muted)}
        .sb-group{margin-top:12px;padding:8px;border:1px solid var(--border);border-radius:12px;background:#fff}
        .sb-label{font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);font-weight:700;padding:4px 8px}
        .nav-link{display:flex;align-items:center;gap:8px;color:#334155;padding:9px 10px;border-radius:10px;font-weight:600}
        .nav-link:hover{background:#f1f5f9;color:#0f172a}
        .nav-link.active{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe}

        .tb-title{font-size:14px;font-weight:700;line-height:1.2}
        .tb-sub{font-size:12px;color:var(--muted)}
        .tb-search{position:relative;width:min(380px,40vw)}
        .tb-search i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8}
        .tb-search input{height:38px;padding:0 12px 0 34px;border:1px solid var(--border);border-radius:999px;background:#fff;width:100%}

        .card{border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow)}
        .table-wrap{border:1px solid var(--border);border-radius:12px;overflow:auto}
        .table thead th{position:sticky;top:0;background:#f8fafc;font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.04em}
        .table tbody tr:hover{background:#f8fafc}

        .empty-state{border:1px dashed #cbd5e1;background:#f8fafc;border-radius:12px;padding:14px;color:#475569}
        @media (max-width:991.98px){.sidebar{display:none}.tb-search{display:none}}
    </style>
    @stack('styles')
</head>
<body>
<div class="app-shell">
    @include('partials.sidebar')
    <div class="content-wrap">
        @include('partials.topbar')
        <main class="page-body">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
