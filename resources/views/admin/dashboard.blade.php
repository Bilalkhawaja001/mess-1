@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('page_title', 'Enterprise Dashboard')

@section('content')
@php
    $users = $stats['users'] ?? 0;
    $members = $stats['members'] ?? 0;
    $openCycles = $stats['open_cycles'] ?? 0;
    $pendingPayments = $stats['pending_payments'] ?? 0;
    $collections = $stats['collections'] ?? null;
    $billable = $stats['billable'] ?? null;
    $collected = $stats['collected'] ?? ($stats['collections'] ?? null);
    $outstanding = $stats['outstanding'] ?? null;
    $recentCycles = $stats['recentCycles'] ?? ($stats['recent_cycles'] ?? []);
    $recentActivity = $stats['recentActivity'] ?? ($stats['recent_activity'] ?? []);
    $dashboardMonthCycle = $stats['dashboard_month_cycle'] ?? null;
    $dashboardCategoryCards = $stats['dashboard_category_cards'] ?? [];
@endphp

<div class="hero-panel p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <h3 class="mb-2 fw-bold">Dashboard</h3>
        </div>
    </div>
</div>

<div class="card p-3 mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-1">Dashboard</h5>
        </div>
        @if($dashboardMonthCycle)
            <span class="badge text-bg-light">Cycle {{ $dashboardMonthCycle }}</span>
        @endif
    </div>
    <div class="row g-2">
        @foreach($dashboardCategoryCards as $card)
            <div class="col-lg-3 col-md-6">
                <div class="p-3 rounded-4 summary-card h-100 dashboard-category-card theme-{{ $card['theme'] ?? 'executive' }}" style="background: {{ ($card['theme'] ?? 'executive') === 'contractors' ? 'linear-gradient(135deg,#f97316,#ea580c)' : (($card['theme'] ?? 'executive') === 'centralized' ? 'linear-gradient(135deg,#8b5cf6,#6d28d9)' : ((($card['theme'] ?? 'executive') === 'guest') ? 'linear-gradient(135deg,#10b981,#047857)' : 'linear-gradient(135deg,#3b82f6,#1d4ed8)')) }}; color:#fff; border:0; box-shadow:0 12px 30px rgba(15,23,42,.12);">
                    <div class="small summary-label">{{ $card['label'] }}</div>
                    <div class="fw-semibold fs-5 summary-value">{{ number_format((float) ($card['total_expenses'] ?? 0), 2) }}</div>
                    <div class="small mt-1 summary-meta">Total Expenses</div>
                    <div class="small mt-2 summary-range">{{ $card['range_label'] ?? '' }}</div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="row g-3 mb-4 dashboard-metrics-row">
    <div class="col-xl col-md-6">
        <div class="card metric-card metric-blue p-3 p-xl-4">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div><div class="metric-label small">Total Users</div><div class="metric-value">{{ $users }}</div><div class="metric-caption small">Active platform users</div></div>
                <div class="metric-icon"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-6">
        <div class="card metric-card metric-purple p-3 p-xl-4">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div><div class="metric-label small">Active Members</div><div class="metric-value">{{ $members }}</div><div class="metric-caption small">Billing-eligible members</div></div>
                <div class="metric-icon"><i class="bi bi-person-badge-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-6">
        <div class="card metric-card metric-emerald p-3 p-xl-4">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div><div class="metric-label small">Open Billing Cycles</div><div class="metric-value">{{ $openCycles }}</div><div class="metric-caption small">Cycles currently in process</div></div>
                <div class="metric-icon"><i class="bi bi-calendar2-week-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-6">
        <div class="card metric-card metric-amber p-3 p-xl-4">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div><div class="metric-label small">Pending Payments</div><div class="metric-value">{{ $pendingPayments }}</div><div class="metric-caption small">Awaiting collection</div></div>
                <div class="metric-icon"><i class="bi bi-cash-coin"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-6">
        <div class="card metric-card metric-rose p-3 p-xl-4">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div><div class="metric-label small">Current Month Collections</div><div class="metric-value">{{ $collections !== null ? number_format((float) $collections, 2) : '—' }}</div><div class="metric-caption small">Recovered in current cycle</div></div>
                <div class="metric-icon"><i class="bi bi-graph-up-arrow"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card p-3 mb-3">
            <h5 class="mb-1">Dashboard</h5>
            <div class="row g-2">
                <div class="col-md-4">
                    <div class="p-3 rounded-4 border bg-white summary-card">
                        <span class="small text-muted summary-label">Billable</span>
                        <div class="fw-semibold fs-5 summary-value">{{ $billable !== null ? number_format((float) $billable, 2) : '—' }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded-4 border bg-white summary-card">
                        <span class="small text-muted summary-label">Collected</span>
                        <div class="fw-semibold fs-5 summary-value">{{ $collected !== null ? number_format((float) $collected, 2) : '—' }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded-4 border bg-white summary-card">
                        <span class="small text-muted summary-label">Outstanding</span>
                        <div class="fw-semibold fs-5 summary-value">{{ $outstanding !== null ? number_format((float) $outstanding, 2) : '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Recent Billing Cycles</h5>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.billing.index') }}">Open Billing</a>
            </div>
            <div class="table-wrap">
                <table class="table mb-0 align-middle">
                    <thead><tr><th>Cycle</th><th>Status</th><th>Summary</th></tr></thead>
                    <tbody>
                    @if(!empty($recentCycles))
                        @foreach($recentCycles as $cycle)
                            <tr>
                                <td>{{ $cycle['month_cycle'] ?? '-' }}</td>
                                <td><span class="badge text-bg-light">{{ $cycle['status'] ?? 'Open' }}</span></td>
                                <td>{{ $cycle['summary'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="3">
                                <div class="empty-state">No active billing cycles found. Create a billing cycle to start the billing process.</div>
                            </td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-3">
            <h5 class="mb-2">Dashboard</h5>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card p-3 mb-3">
            <h5 class="mb-3">Quick Actions</h5>
            <div class="d-grid gap-2">
                <a class="btn btn-outline-primary" href="{{ route('admin.members.index') }}"><i class="bi bi-person-plus me-1"></i> Add Member</a>
                <a class="btn btn-outline-primary" href="{{ route('admin.billing.index') }}"><i class="bi bi-receipt me-1"></i> Create Billing Cycle</a>
                <a class="btn btn-outline-primary" href="{{ route('admin.payments.index') }}"><i class="bi bi-cash-coin me-1"></i> Record Payment</a>
                <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index') }}"><i class="bi bi-bar-chart me-1"></i> View Reports</a>
                <a class="btn btn-outline-secondary" href="{{ route('admin.reports.bills-download') }}"><i class="bi bi-file-earmark-arrow-down me-1"></i> Bills Download</a>
            </div>
        </div>

        <div class="card p-3 mb-3">
            <h5 class="mb-2">Recent Activity</h5>
            @if(!empty($recentActivity))
                <ul class="mb-0 ps-3">
                    @foreach($recentActivity as $a)
                        <li class="mb-2"><strong>{{ $a['title'] ?? 'Activity' }}</strong><br><small class="text-muted">{{ $a['time'] ?? '' }}</small></li>
                    @endforeach
                </ul>
            @else
                <div class="empty-state">No recent activity yet. Activity will appear after transactional events.</div>
            @endif
        </div>

        <div class="card p-3">
            <h5 class="mb-2">Dashboard</h5>
        </div>
    </div>
</div>
@endsection
