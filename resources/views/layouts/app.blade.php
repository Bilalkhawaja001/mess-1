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
    <link rel="stylesheet" href="{{ asset('branding/nodesky-theme.css') }}?v={{ @filemtime(public_path('branding/nodesky-theme.css')) ?: 'saas-ui-v2' }}">
    @stack('styles')
</head>
<body>
@php
    $isMemberBottomNavPage = request()->routeIs('member.dashboard')
        || request()->routeIs('member.statement.*')
        || request()->routeIs('member.payments.*')
        || request()->routeIs('member.complaints.*')
        || request()->routeIs('member.profile.*');
@endphp
<div class="member-app-loading" id="memberAppLoading" aria-hidden="true">
    <div class="member-app-loading__panel">
        <div class="member-app-loading__spinner"></div>
        <div class="member-app-loading__text">Loading dashboard...</div>
    </div>
</div>
<div class="app-shell" id="appShell">
    @include('partials.sidebar')
    <div class="content-wrap">
        @include('partials.topbar')
        <main class="page-body {{ $isMemberBottomNavPage ? 'member-page-body has-bottom-nav' : '' }}">
            <div class="page-container">
                @include('partials.flash')
                @yield('content')
            </div>
        </main>
    </div>
</div>
@if($isMemberBottomNavPage)
    <nav class="member-bottom-nav" aria-label="Member mobile navigation">
        <a href="{{ route('member.dashboard') }}" class="member-bottom-nav__item {{ request()->routeIs('member.dashboard') ? 'is-active' : '' }}">
            <i class="bi bi-grid-1x2"></i>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('member.statement.index') }}" class="member-bottom-nav__item {{ request()->routeIs('member.statement.*') ? 'is-active' : '' }}">
            <i class="bi bi-journal-text"></i>
            <span>Statement</span>
        </a>
        <a href="{{ route('member.payments.index') }}" class="member-bottom-nav__item {{ request()->routeIs('member.payments.*') ? 'is-active' : '' }}">
            <i class="bi bi-credit-card-2-front"></i>
            <span>Payments</span>
        </a>
        <a href="{{ route('member.complaints.index') }}" class="member-bottom-nav__item {{ request()->routeIs('member.complaints.*') ? 'is-active' : '' }}">
            <i class="bi bi-headset"></i>
            <span>Complaints</span>
        </a>
        <a href="{{ route('member.profile.index') }}" class="member-bottom-nav__item {{ request()->routeIs('member.profile.*') ? 'is-active' : '' }}">
            <i class="bi bi-person"></i>
            <span>Profile</span>
        </a>
    </nav>
@endif
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const shell = document.getElementById('appShell');
    const toggle = document.getElementById('sidebarToggle');
    const loadingEl = document.getElementById('memberAppLoading');
    const hideLoader = () => loadingEl && loadingEl.classList.add('is-hidden');

    window.addEventListener('load', hideLoader, { once: true });
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(hideLoader, 180);
        document.querySelectorAll('a[href]').forEach((link) => {
            link.addEventListener('click', () => {
                if (link.target === '_blank' || link.hasAttribute('download') || link.getAttribute('href') === '#') {
                    return;
                }
                loadingEl && loadingEl.classList.remove('is-hidden');
            });
        });
        document.querySelectorAll('form').forEach((form) => {
            form.addEventListener('submit', () => {
                loadingEl && loadingEl.classList.remove('is-hidden');
            });
        });
    });

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
