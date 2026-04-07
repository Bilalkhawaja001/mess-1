@php
    $isMember = auth()->check() && auth()->user()->isMemberRole();
    $path = request()->path();
@endphp
<aside class="sidebar">
    <div class="sb-brand">
        <div class="sb-brand-mark sb-brand-mark-generic">
            <span>MB</span>
        </div>
        <div>
            <div class="sb-title">Mess Billing</div>
            <div class="sb-sub">Corporate Operations Suite</div>
        </div>
    </div>

    @if($isMember)
        <div class="sb-group">
            <div class="sb-label">Member</div>
            <nav class="nav flex-column gap-1">
                <a class="nav-link {{ request()->routeIs('member.dashboard') ? 'active' : '' }}" href="{{ route('member.dashboard') }}"><i class="bi bi-speedometer2"></i>Member Dashboard</a>
            </nav>
        </div>
    @else
        <div class="sb-group">
            <div class="sb-label">Operations</div>
            <nav class="nav flex-column gap-1">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-grid-1x2"></i>Dashboard</a>
                <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><i class="bi bi-people"></i>Users</a>
                <a class="nav-link {{ request()->routeIs('admin.members.*') ? 'active' : '' }}" href="{{ route('admin.members.index') }}"><i class="bi bi-person-lines-fill"></i>Members</a>
                @if(auth()->user()->hasPermission('superadmin.member_account_create'))
                    <a class="nav-link {{ request()->routeIs('admin.member-accounts.*') ? 'active' : '' }}" href="{{ route('admin.member-accounts.index') }}"><i class="bi bi-shield-lock"></i>Member Accounts</a>
                @endif
                <a class="nav-link {{ request()->routeIs('admin.attendance.*') ? 'active' : '' }}" href="{{ route('admin.attendance.index') }}"><i class="bi bi-calendar-check"></i>Attendance</a>
                <a class="nav-link {{ request()->routeIs('admin.attendance-monthly.*') ? 'active' : '' }}" href="{{ route('admin.attendance-monthly.index') }}"><i class="bi bi-calendar3"></i>Monthly Attendance</a>
                <a class="nav-link {{ request()->routeIs('admin.extras.*') ? 'active' : '' }}" href="{{ route('admin.extras.index') }}"><i class="bi bi-plus-square"></i>Extras</a>
                <a class="nav-link {{ request()->routeIs('admin.rates.*') ? 'active' : '' }}" href="{{ route('admin.rates.index') }}"><i class="bi bi-tags"></i>Rates</a>
                <a class="nav-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}" href="{{ route('admin.inventory.index') }}"><i class="bi bi-box-seam"></i>Inventory</a>
                <a class="nav-link {{ request()->routeIs('admin.procurement.*') ? 'active' : '' }}" href="{{ route('admin.procurement.index') }}"><i class="bi bi-truck"></i>Procurement</a>
                <a class="nav-link {{ request()->routeIs('admin.kitchen.*') ? 'active' : '' }}" href="{{ route('admin.kitchen.index') }}"><i class="bi bi-egg-fried"></i>Kitchen</a>
                <a class="nav-link {{ request()->routeIs('admin.guests.*') ? 'active' : '' }}" href="{{ route('admin.guests.index') }}"><i class="bi bi-person-badge"></i>Guests</a>
                <a class="nav-link {{ request()->routeIs('admin.accounting.*') ? 'active' : '' }}" href="{{ route('admin.accounting.index') }}"><i class="bi bi-bank"></i>Accounting</a>
                <a class="nav-link {{ request()->routeIs('admin.exports.*') ? 'active' : '' }}" href="{{ route('admin.exports.index') }}"><i class="bi bi-download"></i>Export Center</a>
            </nav>
        </div>

        <div class="sb-group">
            <div class="sb-label">Billing & Finance</div>
            <nav class="nav flex-column gap-1">
                <a class="nav-link {{ request()->routeIs('admin.billing.*') ? 'active' : '' }}" href="{{ route('admin.billing.index') }}"><i class="bi bi-receipt"></i>Billing</a>
                <a class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}"><i class="bi bi-cash-stack"></i>Payments</a>
                <a class="nav-link {{ request()->routeIs('admin.ledger.*') ? 'active' : '' }}" href="{{ route('admin.ledger.index') }}"><i class="bi bi-journal-text"></i>Ledger</a>
                <a class="nav-link {{ request()->routeIs('admin.summary.*') ? 'active' : '' }}" href="{{ route('admin.summary.index') }}"><i class="bi bi-clipboard-data"></i>Summary</a>
                <a class="nav-link {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}"><i class="bi bi-bar-chart-line"></i>Reports</a>
                <a class="nav-link {{ request()->routeIs('admin.reports.overall-recovery') ? 'active' : '' }}" href="{{ route('admin.reports.overall-recovery') }}"><i class="bi bi-graph-up-arrow"></i>Overall Recovery</a>
                <a class="nav-link {{ request()->routeIs('admin.statement.*') ? 'active' : '' }}" href="{{ route('admin.statement.index') }}"><i class="bi bi-file-earmark-text"></i>Statement</a>
                <a class="nav-link {{ request()->routeIs('admin.month.*') ? 'active' : '' }}" href="{{ route('admin.month.index') }}"><i class="bi bi-calendar2-check"></i>Month Governance</a>
                <a class="nav-link {{ request()->routeIs('admin.audit-log.*') ? 'active' : '' }}" href="{{ route('admin.audit-log.index') }}"><i class="bi bi-journal-check"></i>Audit Log</a>
                <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}"><i class="bi bi-sliders"></i>Settings</a>
            </nav>
        </div>
    @endif

    <div class="sb-powered">
        <div class="sb-powered-logo">
            <picture>
                <source type="image/svg+xml" srcset="{{ asset('branding/nodesky_logo.svg') }}">
                <img src="{{ asset('branding/nodesky_logo.png') }}" alt="NodeSky logo">
            </picture>
        </div>
        <div class="sb-powered-text">Powerd by "NodeSky(smc-Private)Limited"</div>
    </div>
</aside>
