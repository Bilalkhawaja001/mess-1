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
@endphp

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-1 fw-bold">Mess Billing Executive View</h4>
        <div class="text-muted small">Operational + financial snapshot across billing, attendance, and collections.</div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl col-md-6"><div class="card p-3"><div class="text-muted small">Total Users</div><div class="h4 mb-0">{{ $users }}</div><small class="text-muted">Active platform users</small></div></div>
    <div class="col-xl col-md-6"><div class="card p-3"><div class="text-muted small">Active Members</div><div class="h4 mb-0">{{ $members }}</div><small class="text-muted">Billing-eligible members</small></div></div>
    <div class="col-xl col-md-6"><div class="card p-3"><div class="text-muted small">Open Billing Cycles</div><div class="h4 mb-0">{{ $openCycles }}</div><small class="text-muted">Cycles currently in process</small></div></div>
    <div class="col-xl col-md-6"><div class="card p-3"><div class="text-muted small">Pending Payments</div><div class="h4 mb-0">{{ $pendingPayments }}</div><small class="text-muted">Awaiting collection</small></div></div>
    <div class="col-xl col-md-6"><div class="card p-3"><div class="text-muted small">Current Month Collections</div><div class="h4 mb-0">{{ $collections !== null ? number_format((float) $collections, 2) : '—' }}</div><small class="text-muted">Recovered in current cycle</small></div></div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card p-3 mb-3">
            <h5 class="mb-1">Billing Overview</h5>
            <div class="text-muted small mb-3">High-level financial performance summary.</div>
            <div class="row g-2">
                <div class="col-md-4"><div class="p-2 rounded border bg-light-subtle"><span class="small text-muted">Billable</span><div class="fw-semibold">{{ $billable !== null ? number_format((float) $billable, 2) : '—' }}</div></div></div>
                <div class="col-md-4"><div class="p-2 rounded border bg-light-subtle"><span class="small text-muted">Collected</span><div class="fw-semibold">{{ $collected !== null ? number_format((float) $collected, 2) : '—' }}</div></div></div>
                <div class="col-md-4"><div class="p-2 rounded border bg-light-subtle"><span class="small text-muted">Outstanding</span><div class="fw-semibold">{{ $outstanding !== null ? number_format((float) $outstanding, 2) : '—' }}</div></div></div>
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
