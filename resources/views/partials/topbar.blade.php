@php
    $topUser = auth()->user();
    $topRoleCode = $topUser ? optional($topUser->role)->code : null;

    $isDataEntryTop = $topRoleCode === 'DATA_ENTRY';
    $isAdminCoreTop = in_array($topRoleCode, ['SUPER_ADMIN', 'ADMIN'], true);

    $canMemberManageTop = $topUser && $topUser->hasPermission('member.manage');
    $canAttendanceTop = $topUser && $topUser->hasPermission('attendance.manage');
    $canComplaintTop = $topUser && $topUser->hasPermission('complaint.view_all');
    $canGuestTop = $topUser && $topUser->hasPermission('guest.manage');

    $canKitchenTop = $topUser && $topUser->hasPermission('kitchen.manage');
    $canMenuTop = $topUser && $topUser->hasPermission('menu.view');

    $canInventoryTop = $topUser && $topUser->hasPermission('inventory.manage');
    $canProcurementTop = $topUser && $topUser->hasPermission('procurement.manage');

    $canBillingTop = $isAdminCoreTop ||
        ($topUser && (
            $topUser->hasPermission('billing.generate') ||
            $topUser->hasPermission('billing.correct')
        ));

    $canPaymentsTop = $topUser && $topUser->hasPermission('payments.view_admin');

    $canLedgerTop = $topUser && (
        $topUser->hasPermission('payments.view_admin') ||
        $topUser->hasPermission('ledger.adjust') ||
        $topUser->hasPermission('ledger.recompute')
    );

    $canRatesTop = $topUser && $topUser->hasPermission('rates.manage');
    $canAccountingTop = $topUser && $topUser->hasPermission('accounting.manage');

    $canReportViewTop = $topUser && $topUser->hasPermission('report.view');
    $canReportExportTop = $topUser && $topUser->hasPermission('report.export');

    $canUsersTop = $topUser && $topUser->hasPermission('users.manage');
    $canMemberAccountsTop = $topUser && $topUser->hasPermission('superadmin.member_account_create');
    $canAuditTop = $topUser && $topUser->hasPermission('audit.view');
    $canSettingsTop = $topUser && $topUser->hasPermission('settings.dangerous');

    $showOperationsTop =
        $isAdminCoreTop ||
        $canMemberManageTop ||
        $canAttendanceTop ||
        $canComplaintTop ||
        $canGuestTop;

    $showMealsTop =
        $isAdminCoreTop ||
        $canKitchenTop ||
        $canMenuTop;

    $showInventoryTop =
        $isAdminCoreTop ||
        $canInventoryTop ||
        $canProcurementTop;

    $showFinanceTop =
        $isAdminCoreTop ||
        $canBillingTop ||
        $canPaymentsTop ||
        $canLedgerTop ||
        $canRatesTop ||
        $canAccountingTop;

    $showReportsTop =
        $isAdminCoreTop ||
        $canReportViewTop ||
        $canReportExportTop;

    $showSystemTop =
        $isAdminCoreTop ||
        $canUsersTop ||
        $canMemberAccountsTop ||
        $canAuditTop ||
        $canSettingsTop;

    $topHome = $isDataEntryTop
        ? route('admin.attendance.index')
        : route('admin.dashboard');
@endphp

<header class="topbar enterprise-topbar">
    <div class="enterprise-topbar-main">
        <a class="enterprise-brand" href="{{ $topHome }}">
            <span class="enterprise-brand-logo">
                <img src="{{ asset('branding/nodesky-logo.webp') }}" alt="NodeSky logo">
            </span>
            <span class="enterprise-brand-copy">
                <strong>Mess Billing</strong>
                <small>Corporate Operations Suite</small>
            </span>
        </a>

        <div class="enterprise-module-title">
            <span>@yield('page_title', 'Dashboard')</span>
        </div>

        <div class="enterprise-search d-none d-lg-flex">
            <i class="bi bi-search"></i>
            <input type="search" placeholder="Search modules, records, reports..." aria-label="Search">
        </div>

        <div class="enterprise-actions">
            @auth
                <span class="enterprise-user">
                    <i class="bi bi-person-circle"></i>
                    {{ auth()->user()->email ?? auth()->user()->name ?? 'User' }}
                </span>
            @endauth

            <form action="{{ route('logout') }}" method="POST" class="m-0">
                @csrf
                <button class="enterprise-logout" type="submit">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            </form>
        </div>
    </div>

    <nav class="enterprise-nav" aria-label="Main navigation">

        {{-- DATA ENTRY: ONLY AUTHORIZED PAGES --}}
        @if($isDataEntryTop)

            <a class="{{ request()->routeIs('admin.attendance.*') ? 'is-active' : '' }}"
               href="{{ route('admin.attendance.index') }}">
                <i class="bi bi-calendar-check"></i>
                <span>Attendance</span>
            </a>

            <a class="{{ request()->routeIs('admin.attendance-monthly.*') ? 'is-active' : '' }}"
               href="{{ route('admin.attendance-monthly.index') }}">
                <i class="bi bi-calendar3"></i>
                <span>Monthly Attendance</span>
            </a>

            @if($canUsersTop)
                <a class="{{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}"
                   href="{{ route('admin.users.index') }}">
                    <i class="bi bi-people"></i>
                    <span>Users</span>
                </a>
            @endif

        @else

            {{-- DASHBOARD --}}
            @if($isAdminCoreTop)
                <a class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}"
                   href="{{ route('admin.dashboard') }}">
                    <i class="bi bi-grid-1x2"></i>
                    <span>Dashboard</span>
                </a>
            @endif

            {{-- MEMBERS & OPERATIONS --}}
            @if($showOperationsTop)
                <div class="enterprise-nav-group">
                    <button type="button">
                        <i class="bi bi-people"></i>
                        <span>Members & Operations</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>

                    <div class="enterprise-dropdown">

                        @if($canMemberManageTop)
                            <a href="{{ route('admin.members.index') }}">Members</a>
                        @endif

                        @if($canAttendanceTop)
                            <a href="{{ route('admin.attendance.index') }}">Attendance</a>
                            <a href="{{ route('admin.attendance-monthly.index') }}">Monthly Attendance</a>
                        @endif

                        @if($canComplaintTop)
                            <a href="{{ route('admin.member-profile-change-requests.index') }}">
                                Profile Change Requests
                            </a>

                            <a href="{{ route('admin.complaints.index') }}">
                                Complaints / Suggestions
                            </a>
                        @endif

                        @if($canGuestTop)
                            <a href="{{ route('admin.guests.index') }}">Guests</a>
                        @endif

                        @if($canMemberManageTop)
                            <a href="{{ route('admin.extras.index') }}">Extras</a>
                        @endif

                        @if($isAdminCoreTop)
                            <a href="{{ route('admin.hubs.operations') }}">Operations Hub</a>
                        @endif

                    </div>
                </div>
            @endif

            {{-- MEALS --}}
            @if($showMealsTop)
                <div class="enterprise-nav-group">
                    <button type="button">
                        <i class="bi bi-egg-fried"></i>
                        <span>Meals Management</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>

                    <div class="enterprise-dropdown">

                        @if($canKitchenTop)
                            <a href="{{ route('admin.kitchen.index') }}">Kitchen</a>
                        @endif

                        @if($canMenuTop)
                            <a href="{{ route('admin.menu.index') }}">Menu</a>
                        @endif

                        @if($isAdminCoreTop)
                            <a href="{{ route('admin.hubs.meals') }}">Meals Hub</a>
                        @endif

                    </div>
                </div>
            @endif

            {{-- INVENTORY & PROCUREMENT --}}
            @if($showInventoryTop)
                <div class="enterprise-nav-group">

                    <button type="button"
                            class="{{ request()->routeIs('admin.inventory.*') || request()->routeIs('admin.procurement.*') || request()->routeIs('admin.hubs.inventory') ? 'is-active' : '' }}">
                        <i class="bi bi-box-seam"></i>
                        <span>Inventory & Procurement</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>

                    <div class="enterprise-dropdown">

                        @if($canInventoryTop)
                            <a href="{{ route('admin.inventory.index') }}">Inventory</a>
                        @endif

                        @if($canProcurementTop)
                            <a href="{{ route('admin.procurement.index', ['tab' => 'vendors']) }}">Vendors</a>
                            <a href="{{ route('admin.procurement.index') }}">Procurement</a>
                        @endif

                        @if($isAdminCoreTop)
                            <a href="{{ route('admin.hubs.inventory') }}">Inventory Hub</a>
                        @endif

                    </div>
                </div>
            @endif

            {{-- BILLING & FINANCE --}}
            @if($showFinanceTop)
                <div class="enterprise-nav-group">
                    <button type="button">
                        <i class="bi bi-receipt"></i>
                        <span>Billing & Finance</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>

                    <div class="enterprise-dropdown">

                        @if($canBillingTop)
                            <a href="{{ route('admin.billing.index') }}">Billing</a>
                        @endif

                        @if($isAdminCoreTop)
                            <a href="{{ route('admin.bill-publish.index') }}">Bill Publish</a>
                            <a href="{{ route('admin.mess-costing.index') }}">Mess Costing</a>
                            <a href="{{ route('admin.admin-mess-bill.index') }}">Admin Mess Bill</a>
                        @endif

                        @if($canPaymentsTop)
                            <a href="{{ route('admin.payments.index') }}">Payments</a>
                        @endif

                        @if($canLedgerTop)
                            <a href="{{ route('admin.ledger.index') }}">Ledger</a>
                        @endif

                        @if($canRatesTop)
                            <a href="{{ route('admin.rates.index') }}">Rates</a>
                        @endif

                        @if($canAccountingTop)
                            <a href="{{ route('admin.accounting.index') }}">Accounting</a>
                        @endif

                    </div>
                </div>
            @endif

            {{-- REPORTS --}}
            @if($showReportsTop)
                <div class="enterprise-nav-group">
                    <button type="button">
                        <i class="bi bi-bar-chart-line"></i>
                        <span>Reports & Exports</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>

                    <div class="enterprise-dropdown">

                        @if($canReportViewTop)
                            <a href="{{ route('admin.summary.index') }}">Summary</a>
                            <a href="{{ route('admin.reports.index') }}">Reports</a>
                            <a href="{{ route('admin.reports.bills-download') }}">Bills Download</a>
                            <a href="{{ route('admin.reports.overall-recovery') }}">Overall Recovery</a>
                            <a href="{{ route('admin.statement.index') }}">Statement</a>
                            <a href="{{ route('admin.month.index') }}">Month Governance</a>
                        @endif

                        @if($canReportExportTop)
                            <a href="{{ route('admin.exports.index') }}">Export Center</a>
                        @endif

                        @if($isAdminCoreTop)
                            <a href="{{ route('admin.hubs.reports') }}">Reports Hub</a>
                        @endif

                    </div>
                </div>
            @endif

            {{-- SYSTEM ADMIN --}}
            @if($showSystemTop)
                <div class="enterprise-nav-group">
                    <button type="button">
                        <i class="bi bi-shield-lock"></i>
                        <span>System Admin</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>

                    <div class="enterprise-dropdown enterprise-dropdown-right">

                        @if($canUsersTop)
                            <a href="{{ route('admin.users.index') }}">Users</a>
                        @endif

                        @if($canMemberAccountsTop)
                            <a href="{{ route('admin.member-accounts.index') }}">Member Accounts</a>
                        @endif

                        @if($canAuditTop)
                            <a href="{{ route('admin.audit-log.index') }}">Audit Log</a>
                        @endif

                        @if($isAdminCoreTop)
                            <a href="{{ route('admin.announcements.index') }}">Announcements</a>
                        @endif

                        @if($canSettingsTop)
                            <a href="{{ route('admin.settings.index') }}">Settings</a>
                        @endif

                    </div>
                </div>
            @endif

        @endif
    </nav>

    {{-- DATA_ENTRY ko unrelated quick-actions bilkul nahi dikhayenge --}}
    @unless($isDataEntryTop)

        @php
            $showQuickTop =
                $canPaymentsTop ||
                $canReportViewTop ||
                $canProcurementTop ||
                $canBillingTop;
        @endphp

        @if($showQuickTop)
            <div class="enterprise-quick-actions" aria-label="Quick actions">

                @if($canPaymentsTop)
                    <a href="{{ route('admin.payments.index') }}">
                        <i class="bi bi-cash-stack"></i>
                        <span>Payments</span>
                    </a>
                @endif

                @if($canReportViewTop)
                    <a href="{{ route('admin.statement.index') }}">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Statement</span>
                    </a>
                @endif

                @if($canProcurementTop)
                    <a href="{{ route('admin.procurement.index', ['tab' => 'vendors']) }}">
                        <i class="bi bi-shop"></i>
                        <span>Vendors</span>
                    </a>

                    <a href="{{ route('admin.procurement.index') }}">
                        <i class="bi bi-truck"></i>
                        <span>Procurement</span>
                    </a>
                @endif

                @if($canBillingTop)
                    <a href="{{ route('admin.billing.index') }}">
                        <i class="bi bi-receipt"></i>
                        <span>Billing</span>
                    </a>
                @endif

                @if($canReportViewTop)
                    <a href="{{ route('admin.reports.index') }}">
                        <i class="bi bi-bar-chart-line"></i>
                        <span>Reports</span>
                    </a>
                @endif

            </div>
        @endif

    @endunless

</header>


<script>
(function () {
    function resetStyle(dd) {
        [
            'display','position','top','left','right','z-index','visibility',
            'opacity','pointer-events','transform'
        ].forEach(function (prop) {
            dd.style.removeProperty(prop);
        });
        dd.classList.remove('floating-open');
    }

    function closeAllDropdowns() {
        document.querySelectorAll('.enterprise-dropdown').forEach(resetStyle);
        document.querySelectorAll('.enterprise-nav-group.is-open').forEach(function (g) {
            g.classList.remove('is-open');
        });
    }

    function openDropdown(group, btn, dd) {
        closeAllDropdowns();

        var rect = btn.getBoundingClientRect();
        var width = Math.max(240, dd.scrollWidth || 240);
        var left = rect.left;

        if (left + width > window.innerWidth - 12) {
            left = window.innerWidth - width - 12;
        }
        if (left < 8) left = 8;

        group.classList.add('is-open');
        dd.classList.add('floating-open');

        dd.style.setProperty('display', 'grid', 'important');
        dd.style.setProperty('position', 'fixed', 'important');
        dd.style.setProperty('top', (rect.bottom + 8) + 'px', 'important');
        dd.style.setProperty('left', left + 'px', 'important');
        dd.style.setProperty('right', 'auto', 'important');
        dd.style.setProperty('z-index', '999999', 'important');
        dd.style.setProperty('visibility', 'visible', 'important');
        dd.style.setProperty('opacity', '1', 'important');
        dd.style.setProperty('pointer-events', 'auto', 'important');
        dd.style.setProperty('transform', 'none', 'important');
    }

    function boot() {
        document.querySelectorAll('.enterprise-nav-group > button').forEach(function (btn) {
            btn.onclick = function (event) {
                event.preventDefault();
                event.stopPropagation();

                var group = btn.closest('.enterprise-nav-group');
                var dd = group ? group.querySelector('.enterprise-dropdown') : null;
                if (!group || !dd) return;

                if (group.classList.contains('is-open')) {
                    closeAllDropdowns();
                } else {
                    openDropdown(group, btn, dd);
                }
            };
        });

        document.querySelectorAll('.enterprise-dropdown').forEach(function (dd) {
            dd.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        });

        document.addEventListener('click', closeAllDropdowns);
        window.addEventListener('scroll', closeAllDropdowns, true);
        window.addEventListener('resize', closeAllDropdowns);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>


