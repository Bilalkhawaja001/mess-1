@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('page_title', 'Enterprise Dashboard')

@push('styles')
<style>
    .dashboard-hero-inner {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1.25rem;
        flex-wrap: wrap;
    }

    .dashboard-hero-left {
        max-width: 640px;
    }

    .dashboard-hero-right {
        min-width: 220px;
    }

    .dashboard-metrics-row .card.metric-card {
        padding: 0.85rem 1rem !important;
    }

    .dashboard-metrics-row .metric-label {
        font-size: 0.72rem;
    }

    .dashboard-metrics-row .metric-caption {
        font-size: 0.72rem;
    }

    .dashboard-quick-actions .btn {
        padding-top: 0.45rem;
        padding-bottom: 0.45rem;
    }

    .dashboard-billing-overview .summary-card {
        padding: 0.85rem 1rem;
        border-radius: 14px;
    }

    .dashboard-billing-overview .summary-label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .dashboard-billing-overview .summary-value {
        font-size: 1rem;
    }

    .dashboard-recent-cycles table tbody td {
        font-size: 0.82rem;
    }

    .dashboard-recent-cycles .badge {
        font-size: 0.72rem;
    }

    .dashboard-right-col .card {
        margin-bottom: 0.75rem;
    }
</style>
@endpush

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
@endphp

<div class="hero-panel p-4 mb-4">
    <div class="dashboard-hero-inner">
        <div class="dashboard-hero-left">
            <div class="section-kicker mb-2"><i class="bi bi-buildings"></i> Executive Control Layer</div>
            <h4 class="mb-1 fw-bold">Mess Billing Executive View</h4>
            <div class="text-muted small">Operational + financial snapshot across billing, attendance, and collections in a premium light workspace.</div>
        </div>
        <div class="dashboard-hero-right text-md-end">
            <div class="small text-muted text-uppercase fw-semibold mb-1">Live focus</div>
            <div class="fw-semibold small">Collections, member lifecycle, and billing governance</div>
        </div>
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
        <div class="card p-3 mb-3 dashboard-billing-overview">
            <h5 class="mb-1">Billing Overview</h5>
            <div class="text-muted small mb-3">High-level financial performance summary.</div>
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
            <h5 class="mb-2">Attendance Snapshot</h5>
            <div class="text-muted small">Attendance module status and member operations are available from the sidebar modules.</div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card p-3 mb-3">
            <h5 class="mb-2">Quick Actions</h5>
            <div class="d-grid gap-2">
                <a class="btn btn-outline-primary" href="{{ route('admin.members.index') }}"><i class="bi bi-person-plus me-1"></i> Add Member</a>
                <a class="btn btn-outline-primary" href="{{ route('admin.billing.index') }}"><i class="bi bi-receipt me-1"></i> Create Billing Cycle</a>
                <a class="btn btn-outline-primary" href="{{ route('admin.payments.index') }}"><i class="bi bi-cash-coin me-1"></i> Record Payment</a>
                <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index') }}"><i class="bi bi-bar-chart me-1"></i> View Reports</a>
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
            <h5 class="mb-2">Alerts / Notices / System Status</h5>
            <div class="small text-muted">System healthy. No critical service disruption detected.</div>
        </div>
    </div>
</div>
@endsection
