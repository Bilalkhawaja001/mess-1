@php
    $isMember = auth()->check() && auth()->user()->isMemberRole();
    $path = request()->path();
@endphp
<aside class="sidebar sidebar-root">
    <div class="sb-brand sidebar-panel-block">
        <div class="sb-brand-mark">
            <img src="{{ asset('branding/dashboard_logo.png') }}" alt="Mess Billing dashboard logo">
        </div>
        <div>
            <div class="sb-title sidebar-title">Mess Billing</div>
            <div class="sb-sub sidebar-subtitle">Corporate Operations Suite</div>
        </div>
    </div>

    @if($isMember)
        <div class="sb-group sidebar-group">
            <div class="sb-label sidebar-label">Member</div>
            <nav class="nav flex-column gap-1">
                <a class="nav-link sidebar-link {{ request()->routeIs('member.dashboard') ? 'active' : '' }}" href="{{ route('member.dashboard') }}"><i class="bi bi-speedometer2 sidebar-icon"></i><span>Member Dashboard</span></a>
            </nav>
        </div>
    @else
        <div class="sb-group sidebar-group">
            <div class="sb-label sidebar-label">Operations</div>
            <nav class="nav flex-column gap-1">
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-grid-1x2 sidebar-icon"></i><span>Dashboard</span></a>
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><i class="bi bi-people sidebar-icon"></i><span>Users</span></a>
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.members.*') ? 'active' : '' }}" href="{{ route('admin.members.index') }}"><i class="bi bi-person-lines-fill sidebar-icon"></i><span>Members</span></a>
                @if(auth()->user()->hasPermission('superadmin.member_account_create'))
                    <a class="nav-link sidebar-link {{ request()->routeIs('admin.member-accounts.*') ? 'active' : '' }}" href="{{ route('admin.member-accounts.index') }}"><i class="bi bi-shield-lock sidebar-icon"></i><span>Member Accounts</span></a>
                @endif
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.attendance.*') ? 'active' : '' }}" href="{{ route('admin.attendance.index') }}"><i class="bi bi-calendar-check sidebar-icon"></i><span>Attendance</span></a>
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.attendance-monthly.*') ? 'active' : '' }}" href="{{ route('admin.attendance-monthly.index') }}"><i class="bi bi-calendar3 sidebar-icon"></i><span>Monthly Attendance</span></a>
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.extras.*') ? 'active' : '' }}" href="{{ route('admin.extras.index') }}"><i class="bi bi-plus-square sidebar-icon"></i><span>Extras</span></a>
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.rates.*') ? 'active' : '' }}" href="{{ route('admin.rates.index') }}"><i class="bi bi-tags sidebar-icon"></i><span>Rates</span></a>
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}" href="{{ route('admin.inventory.index') }}"><i class="bi bi-box-seam sidebar-icon"></i><span>Inventory</span></a>
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.procurement.*') ? 'active' : '' }}" href="{{ route('admin.procurement.index') }}"><i class="bi bi-truck sidebar-icon"></i><span>Procurement</span></a>
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.kitchen.*') ? 'active' : '' }}" href="{{ route('admin.kitchen.index') }}"><i class="bi bi-egg-fried sidebar-icon"></i><span>Kitchen</span></a>
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.guests.*') ? 'active' : '' }}" href="{{ route('admin.guests.index') }}"><i class="bi bi-person-badge sidebar-icon"></i><span>Guests</span></a>
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.accounting.*') ? 'active' : '' }}" href="{{ route('admin.accounting.index') }}"><i class="bi bi-bank sidebar-icon"></i><span>Accounting</span></a>
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.exports.*') ? 'active' : '' }}" href="{{ route('admin.exports.index') }}"><i class="bi bi-download sidebar-icon"></i><span>Export Center</span></a>
            </nav>
        </div>

        <div class="sb-group sidebar-group">
            <div class="sb-label sidebar-label">Billing & Finance</div>
            <nav class="nav flex-column gap-1">
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.billing.*') ? 'active' : '' }}" href="{{ route('admin.billing.index') }}"><i class="bi bi-receipt sidebar-icon"></i><span>Billing</span></a>
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}"><i class="bi bi-cash-stack sidebar-icon"></i><span>Payments</span></a>
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.ledger.*') ? 'active' : '' }}" href="{{ route('admin.ledger.index') }}"><i class="bi bi-journal-text sidebar-icon"></i><span>Ledger</span></a>
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.summary.*') ? 'active' : '' }}" href="{{ route('admin.summary.index') }}"><i class="bi bi-clipboard-data sidebar-icon"></i><span>Summary</span></a>
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}"><i class="bi bi-bar-chart-line sidebar-icon"></i><span>Reports</span></a>
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.reports.overall-recovery') ? 'active' : '' }}" href="{{ route('admin.reports.overall-recovery') }}"><i class="bi bi-graph-up-arrow sidebar-icon"></i><span>Overall Recovery</span></a>
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.statement.*') ? 'active' : '' }}" href="{{ route('admin.statement.index') }}"><i class="bi bi-file-earmark-text sidebar-icon"></i><span>Statement</span></a>
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.month.*') ? 'active' : '' }}" href="{{ route('admin.month.index') }}"><i class="bi bi-calendar2-check sidebar-icon"></i><span>Month Governance</span></a>
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.audit-log.*') ? 'active' : '' }}" href="{{ route('admin.audit-log.index') }}"><i class="bi bi-journal-check sidebar-icon"></i><span>Audit Log</span></a>
                <a class="nav-link sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}"><i class="bi bi-sliders sidebar-icon"></i><span>Settings</span></a>
            </nav>
        </div>
    @endif

    <div class="sb-powered sidebar-powered">
        <div class="sb-powered-logo">
            <img src="{{ asset('branding/nodesky_logo.png') }}" alt="NodeSky logo">
        </div>
        <div class="sb-powered-text sidebar-powered-text">Powered by NodeSky(smc-Private)Limited</div>
    </div>
</aside>
