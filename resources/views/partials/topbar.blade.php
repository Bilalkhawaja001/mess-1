<header class="topbar">
    <div class="topbar-meta">
        <button class="sidebar-toggle" type="button" id="sidebarToggle" aria-label="Toggle sidebar" aria-controls="appSidebar" aria-expanded="true" title="Toggle sidebar">
            <i class="bi bi-list"></i>
        </button>
        <div>
            <div class="tb-title">@yield('page_title', 'Dashboard')</div>
            <div class="tb-sub">Enterprise billing command center</div>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2">
        <div class="tb-search d-none d-md-block">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search members, bills, payments...">
        </div>

        @auth
            <span class="badge text-bg-light d-none d-md-inline px-3 py-2">{{ auth()->user()->username }}</span>
            <form action="{{ route('logout') }}" method="POST" class="m-0">
                @csrf
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-right me-1"></i>Logout</button>
            </form>
        @endauth
    </div>
</header>
