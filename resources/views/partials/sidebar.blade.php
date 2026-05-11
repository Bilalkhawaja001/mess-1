@php
    $isMember = auth()->check() && auth()->user()->isMemberRole();
    $path = request()->path();
    $opsOpen = request()->routeIs('admin.members.*') || request()->routeIs('admin.attendance.*') || request()->routeIs('admin.attendance-monthly.*') || request()->routeIs('admin.complaints.*') || request()->routeIs('admin.guests.*') || request()->routeIs('admin.extras.*') || request()->routeIs('admin.hubs.operations');
    $invOpen = request()->routeIs('admin.inventory.*') || request()->routeIs('admin.procurement.*') || request()->routeIs('admin.hubs.inventory');
    $mealsOpen = request()->routeIs('admin.kitchen.*') || request()->routeIs('admin.menu.*') || request()->routeIs('admin.hubs.meals');
    $financeOpen = request()->routeIs('admin.billing.*') || request()->routeIs('admin.mess-costing.*') || request()->routeIs('admin.admin-mess-bill.*') || request()->routeIs('admin.payments.*') || request()->routeIs('admin.ledger.*') || request()->routeIs('admin.rates.*') || request()->routeIs('admin.accounting.*');
    $reportsOpen = request()->routeIs('admin.summary.*') || request()->routeIs('admin.reports.*') || request()->routeIs('admin.statement.*') || request()->routeIs('admin.month.*') || request()->routeIs('admin.exports.*') || request()->routeIs('admin.hubs.reports');
    $adminOpen = request()->routeIs('admin.users.*') || request()->routeIs('admin.member-accounts.*') || request()->routeIs('admin.audit-log.*') || request()->routeIs('admin.settings.*');
@endphp
<aside class="sidebar sidebar-root" id="appSidebar">
    <div class="sidebar-inner">
        <div class="sb-brand sidebar-panel-block">
            <div class="sb-brand-mark sb-brand-mark-generic" aria-hidden="true">
                <span>MB</span>
            </div>
            <div class="sb-brand-copy">
                <div class="sb-title sidebar-title">Mess Billing</div>
                <div class="sb-sub sidebar-subtitle">Corporate Operations Suite</div>
            </div>
        </div>

        <div class="sidebar-scroll">
            @if($isMember)
                <div class="sb-group sidebar-group">
                    <div class="sb-label sidebar-label">Member</div>
                    <nav class="nav flex-column gap-1">
                        <a class="nav-link sidebar-link {{ request()->routeIs('member.dashboard') ? 'active' : '' }}" href="{{ route('member.dashboard') }}" title="Member Dashboard"><span class="sidebar-icon-wrap"><i class="bi bi-speedometer2 sidebar-icon"></i></span><span>Member Dashboard</span></a>
                        @if(auth()->user()->hasPermission('payments.view_own') && auth()->user()->hasLinkedMemberProfile())
                            <a class="nav-link sidebar-link {{ request()->routeIs('member.payments.*') ? 'active' : '' }}" href="{{ route('member.payments.index') }}" title="Payment"><span class="sidebar-icon-wrap"><i class="bi bi-cash-stack sidebar-icon"></i></span><span>Payment</span></a>
                            <a class="nav-link sidebar-link {{ request()->routeIs('member.statement.*') ? 'active' : '' }}" href="{{ route('member.statement.index') }}" title="Statement"><span class="sidebar-icon-wrap"><i class="bi bi-journal-text sidebar-icon"></i></span><span>Statement</span></a>
                            <a class="nav-link sidebar-link {{ request()->routeIs('member.profile.*') ? 'active' : '' }}" href="{{ route('member.profile.index') }}" title="My Profile"><span class="sidebar-icon-wrap"><i class="bi bi-person-vcard sidebar-icon"></i></span><span>My Profile</span></a>
                        @endif
                        @if(auth()->user()->hasPermission('complaint.view_own'))
                            <a class="nav-link sidebar-link {{ request()->routeIs('member.complaints.*') ? 'active' : '' }}" href="{{ route('member.complaints.index') }}" title="My Complaints / Suggestions"><span class="sidebar-icon-wrap"><i class="bi bi-chat-left-text sidebar-icon"></i></span><span>Complaint / Suggestion</span></a>
                        @endif
                        @if(auth()->user()->hasPermission('menu.view'))
                            <a class="nav-link sidebar-link {{ request()->routeIs('member.menu.*') ? 'active' : '' }}" href="{{ route('member.menu.index') }}" title="Menu"><span class="sidebar-icon-wrap"><i class="bi bi-card-list sidebar-icon"></i></span><span>Menu</span></a>
                        @endif
                    </nav>
                </div>
            @else
                <div class="sb-group sidebar-group">
                    <div class="sb-label sidebar-label">Dashboard</div>
                    <nav class="nav flex-column gap-1">
                        <a class="nav-link sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}" title="Dashboard"><span class="sidebar-icon-wrap"><i class="bi bi-grid-1x2 sidebar-icon"></i></span><span>Dashboard</span></a>
                    </nav>
                </div>

                <div class="sb-group sidebar-group">
                    <button class="sb-label sidebar-label btn btn-link text-start text-decoration-none p-0 w-100 d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-operations" aria-expanded="{{ $opsOpen ? 'true' : 'false' }}" aria-controls="sidebar-operations">Operations <i class="bi {{ $opsOpen ? 'bi-chevron-up' : 'bi-chevron-down' }}"></i></button>
                    <nav class="nav flex-column gap-1 collapse {{ $opsOpen ? 'show' : '' }}" id="sidebar-operations">
                        @if(auth()->user()->hasPermission('member.manage'))
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.members.*') ? 'active' : '' }}" href="{{ route('admin.members.index') }}" title="Members"><span class="sidebar-icon-wrap"><i class="bi bi-person-lines-fill sidebar-icon"></i></span><span>Members</span></a>
                        @endif
                        @if(auth()->user()->hasPermission('attendance.manage'))
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.attendance.*') ? 'active' : '' }}" href="{{ route('admin.attendance.index') }}" title="Attendance"><span class="sidebar-icon-wrap"><i class="bi bi-calendar-check sidebar-icon"></i></span><span>Attendance</span></a>
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.attendance-monthly.*') ? 'active' : '' }}" href="{{ route('admin.attendance-monthly.index') }}" title="Monthly Attendance"><span class="sidebar-icon-wrap"><i class="bi bi-calendar3 sidebar-icon"></i></span><span>Monthly Attendance</span></a>
                        @endif
                        <a class="nav-link sidebar-link {{ request()->routeIs('admin.member-profile-change-requests.*') ? 'active' : '' }}" href="{{ route('admin.member-profile-change-requests.index') }}" title="Profile Change Requests"><span class="sidebar-icon-wrap"><i class="bi bi-person-gear sidebar-icon"></i></span><span>Profile Change Requests</span></a>
                        @if(auth()->user()->hasPermission('guest.manage'))
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.guests.*') ? 'active' : '' }}" href="{{ route('admin.guests.index') }}" title="Guests"><span class="sidebar-icon-wrap"><i class="bi bi-person-badge sidebar-icon"></i></span><span>Guests</span></a>
                        @endif
                        @if(auth()->user()->hasPermission('member.manage'))
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.extras.*') ? 'active' : '' }}" href="{{ route('admin.extras.index') }}" title="Extras"><span class="sidebar-icon-wrap"><i class="bi bi-plus-square sidebar-icon"></i></span><span>Extras</span></a>
                        @endif
                        <a class="nav-link sidebar-link {{ request()->routeIs('admin.hubs.operations') ? 'active' : '' }}" href="{{ route('admin.hubs.operations') }}" title="Operations Hub"><span class="sidebar-icon-wrap"><i class="bi bi-columns-gap sidebar-icon"></i></span><span>Operations Hub</span></a>
                    </nav>
                </div>

                <div class="sb-group sidebar-group">
                    <button class="sb-label sidebar-label btn btn-link text-start text-decoration-none p-0 w-100 d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-inventory" aria-expanded="{{ $invOpen ? 'true' : 'false' }}" aria-controls="sidebar-inventory">Inventory & Procurement <i class="bi {{ $invOpen ? 'bi-chevron-up' : 'bi-chevron-down' }}"></i></button>
                    <nav class="nav flex-column gap-1 collapse {{ $invOpen ? 'show' : '' }}" id="sidebar-inventory">
                        @if(auth()->user()->hasPermission('inventory.manage'))
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}" href="{{ route('admin.inventory.index') }}" title="Inventory"><span class="sidebar-icon-wrap"><i class="bi bi-box-seam sidebar-icon"></i></span><span>Inventory</span></a>
                        @endif
                        @if(auth()->user()->hasPermission('procurement.manage'))
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.procurement.*') ? 'active' : '' }}" href="{{ route('admin.procurement.index') }}" title="Procurement"><span class="sidebar-icon-wrap"><i class="bi bi-truck sidebar-icon"></i></span><span>Procurement</span></a>
                        @endif
                        <a class="nav-link sidebar-link {{ request()->routeIs('admin.hubs.inventory') ? 'active' : '' }}" href="{{ route('admin.hubs.inventory') }}" title="Inventory Hub"><span class="sidebar-icon-wrap"><i class="bi bi-boxes sidebar-icon"></i></span><span>Inventory Hub</span></a>
                    </nav>
                </div>

                <div class="sb-group sidebar-group">
                    <button class="sb-label sidebar-label btn btn-link text-start text-decoration-none p-0 w-100 d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-meals" aria-expanded="{{ $mealsOpen ? 'true' : 'false' }}" aria-controls="sidebar-meals">Kitchen & Meals <i class="bi {{ $mealsOpen ? 'bi-chevron-up' : 'bi-chevron-down' }}"></i></button>
                    <nav class="nav flex-column gap-1 collapse {{ $mealsOpen ? 'show' : '' }}" id="sidebar-meals">
                        @if(auth()->user()->hasPermission('kitchen.manage'))
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.kitchen.*') ? 'active' : '' }}" href="{{ route('admin.kitchen.index') }}" title="Kitchen"><span class="sidebar-icon-wrap"><i class="bi bi-egg-fried sidebar-icon"></i></span><span>Kitchen</span></a>
                        @endif
                        @if(auth()->user()->hasPermission('menu.view'))
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.menu.*') ? 'active' : '' }}" href="{{ route('admin.menu.index') }}" title="Menu"><span class="sidebar-icon-wrap"><i class="bi bi-card-list sidebar-icon"></i></span><span>Menu</span></a>
                        @endif
                        <a class="nav-link sidebar-link {{ request()->routeIs('admin.hubs.meals') ? 'active' : '' }}" href="{{ route('admin.hubs.meals') }}" title="Meals Hub"><span class="sidebar-icon-wrap"><i class="bi bi-grid-3x3-gap sidebar-icon"></i></span><span>Meals Hub</span></a>
                    </nav>
                </div>

                <div class="sb-group sidebar-group">
                    <button class="sb-label sidebar-label btn btn-link text-start text-decoration-none p-0 w-100 d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-finance" aria-expanded="{{ $financeOpen ? 'true' : 'false' }}" aria-controls="sidebar-finance">Billing & Finance <i class="bi {{ $financeOpen ? 'bi-chevron-up' : 'bi-chevron-down' }}"></i></button>
                    <nav class="nav flex-column gap-1 collapse {{ $financeOpen ? 'show' : '' }}" id="sidebar-finance">
                        <a class="nav-link sidebar-link {{ request()->routeIs('admin.billing.*') ? 'active' : '' }}" href="{{ route('admin.billing.index') }}" title="Billing"><span class="sidebar-icon-wrap"><i class="bi bi-receipt sidebar-icon"></i></span><span>Billing</span></a>
                        <a class="nav-link sidebar-link {{ request()->routeIs('admin.mess-costing.*') ? 'active' : '' }}" href="{{ route('admin.mess-costing.index') }}" title="Mess Costing"><span class="sidebar-icon-wrap"><i class="bi bi-calculator sidebar-icon"></i></span><span>Mess Costing</span></a>
                        <a class="nav-link sidebar-link {{ request()->routeIs('admin.admin-mess-bill.*') ? 'active' : '' }}" href="{{ route('admin.admin-mess-bill.index') }}" title="Admin Mess Bill"><span class="sidebar-icon-wrap"><i class="bi bi-file-earmark-text sidebar-icon"></i></span><span>Admin Mess Bill</span></a>
                        @if(auth()->user()->hasPermission('payments.view_admin'))
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}" title="Payments"><span class="sidebar-icon-wrap"><i class="bi bi-cash-stack sidebar-icon"></i></span><span>Payments</span></a>
                        @endif
                        @if(auth()->user()->hasPermission('payments.view_admin') || auth()->user()->hasPermission('ledger.adjust') || auth()->user()->hasPermission('ledger.recompute') || auth()->user()->hasPermission('report.view'))
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.ledger.*') ? 'active' : '' }}" href="{{ route('admin.ledger.index') }}" title="Ledger"><span class="sidebar-icon-wrap"><i class="bi bi-journal-text sidebar-icon"></i></span><span>Ledger</span></a>
                        @endif
                        @if(auth()->user()->hasPermission('rates.manage'))
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.rates.*') ? 'active' : '' }}" href="{{ route('admin.rates.index') }}" title="Rates"><span class="sidebar-icon-wrap"><i class="bi bi-tags sidebar-icon"></i></span><span>Rates</span></a>
                        @endif
                        @if(auth()->user()->hasPermission('accounting.manage'))
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.accounting.*') ? 'active' : '' }}" href="{{ route('admin.accounting.index') }}" title="Accounting"><span class="sidebar-icon-wrap"><i class="bi bi-bank sidebar-icon"></i></span><span>Accounting</span></a>
                        @endif
                    </nav>
                </div>

                <div class="sb-group sidebar-group">
                    <button class="sb-label sidebar-label btn btn-link text-start text-decoration-none p-0 w-100 d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-reports" aria-expanded="{{ $reportsOpen ? 'true' : 'false' }}" aria-controls="sidebar-reports">Reports <i class="bi {{ $reportsOpen ? 'bi-chevron-up' : 'bi-chevron-down' }}"></i></button>
                    <nav class="nav flex-column gap-1 collapse {{ $reportsOpen ? 'show' : '' }}" id="sidebar-reports">
                        @if(auth()->user()->hasPermission('report.view'))
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.summary.*') ? 'active' : '' }}" href="{{ route('admin.summary.index') }}" title="Summary"><span class="sidebar-icon-wrap"><i class="bi bi-clipboard-data sidebar-icon"></i></span><span>Summary</span></a>
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}" title="Reports"><span class="sidebar-icon-wrap"><i class="bi bi-bar-chart-line sidebar-icon"></i></span><span>Reports</span></a>

                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.reports.bills-download*') ? 'active' : '' }}" href="{{ route('admin.reports.bills-download') }}" title="Bills Download"><span class="sidebar-icon-wrap"><i class="bi bi-file-earmark-arrow-down sidebar-icon"></i></span><span>Bills Download</span></a>

                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.reports.overall-recovery') ? 'active' : '' }}" href="{{ route('admin.reports.overall-recovery') }}" title="Overall Recovery"><span class="sidebar-icon-wrap"><i class="bi bi-graph-up-arrow sidebar-icon"></i></span><span>Overall Recovery</span></a>
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.statement.*') ? 'active' : '' }}" href="{{ route('admin.statement.index') }}" title="Statement"><span class="sidebar-icon-wrap"><i class="bi bi-file-earmark-text sidebar-icon"></i></span><span>Statement</span></a>
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.month.*') ? 'active' : '' }}" href="{{ route('admin.month.index') }}" title="Month Governance"><span class="sidebar-icon-wrap"><i class="bi bi-calendar2-check sidebar-icon"></i></span><span>Month Governance</span></a>
                        @endif
                        @if(auth()->user()->hasPermission('report.export'))
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.exports.*') ? 'active' : '' }}" href="{{ route('admin.exports.index') }}" title="Export Center"><span class="sidebar-icon-wrap"><i class="bi bi-download sidebar-icon"></i></span><span>Export Center</span></a>
                        @endif
                        <a class="nav-link sidebar-link {{ request()->routeIs('admin.hubs.reports') ? 'active' : '' }}" href="{{ route('admin.hubs.reports') }}" title="Reports Hub"><span class="sidebar-icon-wrap"><i class="bi bi-bar-chart-steps sidebar-icon"></i></span><span>Reports Hub</span></a>
                    </nav>
                </div>

                <div class="sb-group sidebar-group">
                    <button class="sb-label sidebar-label btn btn-link text-start text-decoration-none p-0 w-100 d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-admin" aria-expanded="{{ $adminOpen ? 'true' : 'false' }}" aria-controls="sidebar-admin">Admin <i class="bi {{ $adminOpen ? 'bi-chevron-up' : 'bi-chevron-down' }}"></i></button>
                    <nav class="nav flex-column gap-1 collapse {{ $adminOpen ? 'show' : '' }}" id="sidebar-admin">
                        @if(auth()->user()->hasPermission('users.manage'))
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}" title="Users"><span class="sidebar-icon-wrap"><i class="bi bi-people sidebar-icon"></i></span><span>Users</span></a>
                        @endif
                        @if(auth()->user()->hasPermission('superadmin.member_account_create'))
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.member-accounts.*') ? 'active' : '' }}" href="{{ route('admin.member-accounts.index') }}" title="Member Accounts"><span class="sidebar-icon-wrap"><i class="bi bi-shield-lock sidebar-icon"></i></span><span>Member Accounts</span></a>
                        @endif
                        @if(auth()->user()->hasPermission('audit.view'))
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.audit-log.*') ? 'active' : '' }}" href="{{ route('admin.audit-log.index') }}" title="Audit Log"><span class="sidebar-icon-wrap"><i class="bi bi-journal-check sidebar-icon"></i></span><span>Audit Log</span></a>
                        @endif
                        @if(auth()->user()->hasPermission('settings.dangerous'))
                            <a class="nav-link sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}" title="Settings"><span class="sidebar-icon-wrap"><i class="bi bi-sliders sidebar-icon"></i></span><span>Settings</span></a>
                        @endif
                    </nav>
                </div>
            @endif
        </div>

        <div class="sb-powered sidebar-powered">
            <div class="sb-powered-logo">
                <img src="{{ asset('branding/nodesky_logo.png') }}" alt="NodeSky logo">
            </div>
            <div class="sb-powered-text sidebar-powered-text">Powered by NodeSky(smc-Private)Limited</div>
        </div>
    </div>
</aside>

