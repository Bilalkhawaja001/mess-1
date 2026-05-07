<header class="topbar">
    <div class="topbar-meta">
        <button class="sidebar-toggle" type="button" id="sidebarToggle" aria-label="Toggle sidebar" aria-controls="appSidebar" aria-expanded="true" title="Toggle sidebar">
            <i class="bi bi-list"></i>
            <span class="visually-hidden">Toggle sidebar</span>
        </button>
        <div>
            <div class="tb-title">@yield('page_title', 'Dashboard')</div>
            <div class="tb-sub">Premium operations workspace with unified enterprise UI</div>
        </div>
    </div>

    <div class="topbar-tools">
        <div class="tb-search d-none d-lg-block">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search modules, records, reports..." readonly>
        </div>

        @auth
            <div class="tb-chip d-none d-md-inline-flex"><i class="bi bi-person-circle"></i><span>{{ auth()->user()->username }}</span></div>
            <form action="{{ route('logout') }}" method="POST" class="m-0">
                @csrf
                <button class="btn btn-sm btn-outline-secondary ui-btn ui-btn-light"><i class="bi bi-box-arrow-right me-1"></i>Logout</button>
            </form>
        @endauth
    </div>
</header>
