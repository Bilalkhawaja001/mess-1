@php
    $isMember = auth()->check() && auth()->user()->isMemberRole();
    $path = request()->path();
@endphp
<aside class="sidebar">
    <div class="sidebar-inner">
        <div class="sb-brand">
            <div class="sb-brand-mark sb-brand-mark-logo">
                <img src="{{ asset('branding/dashboard_logo.png') }}" alt="Mess Billing logo">
            </div>
            <div class="sb-brand-copy">
                <div class="sb-title">Mess Billing</div>
                <div class="sb-sub">Corporate Operations Suite</div>
            </div>
        </div>

        <div class="sb-nav-wrap">
            @if($isMember)
                <div class="sb-group">
                    <div class="sb-label">Member</div>
                    <nav class="nav sb-nav flex-column gap-1">
                        <a class="nav-link {{ request()->routeIs('member.dashboard') ? 'active' : '' }}" href="{{ route('member.dashboard') }}"><span class="sb-icon"><i class="bi bi-speedometer2"></i></span><span class="sb-link-copy">Member Dashboard</span></a>
                    </nav>
                </div>
            @else
                <div class="sb-group">
                    <div class="sb-label">Operations</div>
                    <nav class="nav sb-nav flex-column gap-1">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><span class="sb-icon"><i class="bi bi-grid-1x2"></i></span><span class="sb-link-copy">Dashboard</span></a>
                        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><span class="sb-icon"><i class="bi bi-people"></i></span><span class="sb-link-copy">Users</span></a>
                        <a class="nav-link {{ request()->routeIs('admin.members.*') ? 'active' : '' }}" href="{{ route('admin.members.index') }}"><span class="sb-icon"><i class="bi bi-person-lines-fill"></i></span><span class="sb-link-copy">Members</span></a>
                        @if(auth()->user()->hasPermission('superadmin.member_account_create'))
                            <a class="nav-link {{ request()->routeIs('admin.member-accounts.*') ? 'active' : '' }}" href="{{ route('admin.member-accounts.index') }}"><span class="sb-icon"><i class="bi bi-shield-lock"></i></span><span class="sb-link-copy">Member Accounts</span></a>
                        @endif
                        <a class="nav-link {{ request()->routeIs('admin.attendance.*') ? 'active' : '' }}" href="{{ route('admin.attendance.index') }}"><span class="sb-icon"><i class="bi bi-calendar-check"></i></span><span class="sb-link-copy">Attendance</span></a>
                        <a class="nav-link {{ request()->routeIs('admin.attendance-monthly.*') ? 'active' : '' }}" href="{{ route('admin.attendance-monthly.index') }}"><span class="sb-icon"><i class="bi bi-calendar3"></i></span><span class="sb-link-copy">Monthly Attendance</span></a>
                        <a class="nav-link {{ request()->routeIs('admin.extras.*') ? 'active' : '' }}" href="{{ route('admin.extras.index') }}"><span class="sb-icon"><i class="bi bi-plus-square"></i></span><span class="sb-link-copy">Extras</span></a>
                        <a class="nav-link {{ request()->routeIs('admin.rates.*') ? 'active' : '' }}" href="{{ route('admin.rates.index') }}"><span class="sb-icon"><i class="bi bi-tags"></i></span><span class="sb-link-copy">Rates</span></a>
                        <a class="nav-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}" href="{{ route('admin.inventory.index') }}"><span class="sb-icon"><i class="bi bi-box-seam"></i></span><span class="sb-link-copy">Inventory</span></a>
                        <a class="nav-link {{ request()->routeIs('admin.procurement.*') ? 'active' : '' }}" href="{{ route('admin.procurement.index') }}"><span class="sb-icon"><i class="bi bi-truck"></i></span><span class="sb-link-copy">Procurement</span></a>
                        <a class="nav-link {{ request()->routeIs('admin.kitchen.*') ? 'active' : '' }}" href="{{ route('admin.kitchen.index') }}"><span class="sb-icon"><i class="bi bi-egg-fried"></i></span><span class="sb-link-copy">Kitchen</span></a>
                        <a class="nav-link {{ request()->routeIs('admin.guests.*') ? 'active' : '' }}" href="{{ route('admin.guests.index') }}"><span class="sb-icon"><i class="bi bi-person-badge"></i></span><span class="sb-link-copy">Guests</span></a>
                        <a class="nav-link {{ request()->routeIs('admin.accounting.*') ? 'active' : '' }}" href="{{ route('admin.accounting.index') }}"><span class="sb-icon"><i class="bi bi-bank"></i></span><span class="sb-link-copy">Accounting</span></a>
                        <a class="nav-link {{ request()->routeIs('admin.exports.*') ? 'active' : '' }}" href="{{ route('admin.exports.index') }}"><span class="sb-icon"><i class="bi bi-download"></i></span><span class="sb-link-copy">Export Center</span></a>
                    </nav>
                </div>

                <div class="sb-group">
                    <div class="sb-label">Billing & Finance</div>
                    <nav class="nav sb-nav flex-column gap-1">
                        <a class="nav-link {{ request()->routeIs('admin.billing.*') ? 'active' : '' }}" href="{{ route('admin.billing.index') }}"><span class="sb-icon"><i class="bi bi-receipt"></i></span><span class="sb-link-copy">Billing</span></a>
                        <a class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}"><span class="sb-icon"><i class="bi bi-cash-stack"></i></span><span class="sb-link-copy">Payments</span></a>
                        <a class="nav-link {{ request()->routeIs('admin.ledger.*') ? 'active' : '' }}" href="{{ route('admin.ledger.index') }}"><span class="sb-icon"><i class="bi bi-journal-text"></i></span><span class="sb-link-copy">Ledger</span></a>
                        <a class="nav-link {{ request()->routeIs('admin.summary.*') ? 'active' : '' }}" href="{{ route('admin.summary.index') }}"><span class="sb-icon"><i class="bi bi-clipboard-data"></i></span><span class="sb-link-copy">Summary</span></a>
                        <a class="nav-link {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}"><span class="sb-icon"><i class="bi bi-bar-chart-line"></i></span><span class="sb-link-copy">Reports</span></a>
                        <a class="nav-link {{ request()->routeIs('admin.reports.overall-recovery') ? 'active' : '' }}" href="{{ route('admin.reports.overall-recovery') }}"><span class="sb-icon"><i class="bi bi-graph-up-arrow"></i></span><span class="sb-link-copy">Overall Recovery</span></a>
                        <a class="nav-link {{ request()->routeIs('admin.statement.*') ? 'active' : '' }}" href="{{ route('admin.statement.index') }}"><span class="sb-icon"><i class="bi bi-file-earmark-text"></i></span><span class="sb-link-copy">Statement</span></a>
                        <a class="nav-link {{ request()->routeIs('admin.month.*') ? 'active' : '' }}" href="{{ route('admin.month.index') }}"><span class="sb-icon"><i class="bi bi-calendar2-check"></i></span><span class="sb-link-copy">Month Governance</span></a>
                        <a class="nav-link {{ request()->routeIs('admin.audit-log.*') ? 'active' : '' }}" href="{{ route('admin.audit-log.index') }}"><span class="sb-icon"><i class="bi bi-journal-check"></i></span><span class="sb-link-copy">Audit Log</span></a>
                        <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}"><span class="sb-icon"><i class="bi bi-sliders"></i></span><span class="sb-link-copy">Settings</span></a>
                    </nav>
                </div>
            @endif
        </div>

        <div class="sb-powered">
            <div class="sb-powered-logo">
                <img src="{{ asset('branding/nodesky_logo.png') }}" alt="NodeSky logo">
            </div>
            <div class="sb-powered-text">Powerd by "NodeSky(smc-Private)Limited"</div>
        </div>
    </div>
</aside>
