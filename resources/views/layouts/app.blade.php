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
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('branding/nodesky-theme.css') }}?v=7c4e9ab">
</head>
<body>
<div class="app-shell" id="appShell">
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
<script>
(() => {
    const shell = document.getElementById('appShell');
    const toggle = document.getElementById('sidebarToggle');
    if (!shell || !toggle) return;

    const storageKey = 'messBilling.sidebarCollapsed';
    const mobileQuery = window.matchMedia('(max-width: 991.98px)');

    const applyState = (collapsed) => {
        shell.classList.toggle('sidebar-collapsed', collapsed);
        toggle.setAttribute('aria-expanded', String(!collapsed));
        toggle.innerHTML = '<i class="bi bi-list"></i>';
    };

    const applyMobileState = (open) => {
        shell.classList.toggle('sidebar-mobile-open', open);
    };

    const saved = localStorage.getItem(storageKey);
    applyState(saved === '1');

    toggle.addEventListener('click', () => {
        if (mobileQuery.matches) {
            applyMobileState(!shell.classList.contains('sidebar-mobile-open'));
            return;
        }
        const next = !shell.classList.contains('sidebar-collapsed');
        localStorage.setItem(storageKey, next ? '1' : '0');
        applyState(next);
    });

    mobileQuery.addEventListener('change', (event) => {
        if (event.matches) {
            applyMobileState(false);
        } else {
            const latest = localStorage.getItem(storageKey) === '1';
            applyState(latest);
        }
    });

    document.addEventListener('click', (event) => {
        if (!mobileQuery.matches) return;
        if (!shell.classList.contains('sidebar-mobile-open')) return;
        const sidebar = document.getElementById('appSidebar');
        if (sidebar && sidebar.contains(event.target)) return;
        if (toggle.contains(event.target)) return;
        applyMobileState(false);
    });
})();
</script>
@stack('scripts')
</body>
</html>
