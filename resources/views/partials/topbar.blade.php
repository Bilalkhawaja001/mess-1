@php($isMemberTopbar = auth()->check() && auth()->user()->isMemberRole())
<header class="topbar {{ $isMemberTopbar ? 'member-mobile-topbar-shell' : '' }}">
    <div class="topbar-left">
        <button class="sidebar-toggle" type="button" id="sidebarToggle" aria-label="Toggle sidebar" aria-controls="appSidebar" aria-expanded="true" title="Toggle sidebar">
            <i class="bi bi-list"></i>
            <span class="visually-hidden">Toggle sidebar</span>
        </button>
        <div class="tb-title">@yield('page_title', 'Dashboard')</div>
    </div>

    <div class="topbar-center d-none d-lg-flex">
        <div class="tb-search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search modules, records, reports..." readonly>
        </div>
    </div>

    <div class="topbar-right">
        @auth
            @if($isMemberTopbar)
                <button type="button" class="member-icon-btn" aria-label="Notifications">
                    <i class="bi bi-bell"></i>
                </button>
                <div class="member-avatar-chip" aria-label="{{ auth()->user()->username }}">
                    {{ strtoupper(substr(auth()->user()->username ?? auth()->user()->name ?? 'M', 0, 1)) }}
                </div>
            @else
                <div class="tb-chip d-none d-md-inline-flex"><i class="bi bi-person-circle"></i><span>{{ auth()->user()->username }}</span></div>
                <form action="{{ route('logout') }}" method="POST" class="m-0 topbar-logout-form">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary ui-btn ui-btn-light"><i class="bi bi-box-arrow-right me-1"></i>Logout</button>
                </form>
            @endif
        @endauth
    </div>
</header>
