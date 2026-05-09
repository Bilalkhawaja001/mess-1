@php
    $isMember = auth()->check() && auth()->user()->isMemberRole();
    $path = request()->path();
    $opsOpen = request()->routeIs('admin.members.*') || request()->routeIs('admin.attendance.*') || request()->routeIs('admin.attendance-monthly.*') || request()->routeIs('admin.complaints.*') || request()->routeIs('admin.guests.*') || request()->routeIs('admin.extras.*') || request()->routeIs('admin.hubs.operations');
    $invOpen = request()->routeIs('admin.inventory.*') || request()->routeIs('admin.procurement.*') || request()->routeIs('admin.hubs.inventory');
    $mealsOpen = request()->routeIs('admin.kitchen.*') || request()->routeIs('admin.menu.*') || request()->routeIs('admin.hubs.meals');
    $financeOpen = request()->routeIs('admin.billing.*') || request()->routeIs('admin.mess-costing.*') || request()->routeIs('admin.payments.*') || request()->routeIs('admin.ledger.*') || request()->routeIs('admin.rates.*') || request()->routeIs('admin.accounting.*');
    $reportsOpen = request()->routeIs('admin.summary.*') || request()->routeIs('admin.reports.*') || request()->routeIs('admin.statement.*') || request()->routeIs('admin.month.*') || request()->routeIs('admin.exports.*') || request()->routeIs('admin.hubs.reports');
    $adminOpen = request()->routeIs('admin.users.*') || request()->routeIs('admin.member-accounts.*') || request()->routeIs('admin.audit-log.*') || request()->routeIs('admin.settings.*');
@endphp
<aside class="sidebar sidebar-root {{ $isMember ? 'member-sidebar' : '' }}" id="appSidebar">
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
