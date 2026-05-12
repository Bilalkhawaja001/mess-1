@extends('layouts.app')

@section('title', 'Billing')
@section('page_title', 'Generate Monthly Bill')

@section('content')
@php
    $billingRows = $rows ?? collect();
    $billingMonths = $months ?? collect();
    $lockedCount = $billingRows->where('is_locked', true)->count();
    $unlockedCount = $billingRows->where('is_locked', false)->count();
    $billingTotalNet = $billingRows->sum(function ($row) {
        return (float) ($row->net_payable ?? 0);
    });
    $billingAverageRate = $billingRows->count() ? $billingRows->avg('rate_per_day') : 0;
@endphp

<div class="page-hero page-hero-compact mb-4">
    <div>
        <h1 class="page-hero-title">Billing</h1>
    </div>
</div>

<div class="stats-grid stats-grid-4 mb-4">
    <div class="stat-card stat-card-primary">
        <div class="stat-label">Generated Months</div>
        <div class="stat-value">{{ $billingMonths->count() }}</div>
        <div class="stat-help">Available month cycles</div>
    </div>
    <div class="stat-card stat-card-success">
        <div class="stat-label">Locked Rows</div>
        <div class="stat-value">{{ $lockedCount }}</div>
        <div class="stat-help">Closed billing records</div>
    </div>
    <div class="stat-card stat-card-warning">
        <div class="stat-label">Net Payable</div>
        <div class="stat-value">{{ number_format($billingTotalNet, 2) }}</div>
        <div class="stat-help">Visible filtered billing total</div>
    </div>
    <div class="stat-card stat-card-info">
        <div class="stat-label">Avg Daily Rate</div>
        <div class="stat-value">{{ number_format((float) $billingAverageRate, 2) }}</div>
        <div class="stat-help">Calculated from visible rows</div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="section-heading mb-3">
            <div>
                <h5 class="mb-1">Billing</h5>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.billing.generate') }}" class="row g-3 align-items-end mb-0">
            @csrf
            <div class="col-lg-3 col-md-4">
                <label class="form-label">Month</label>
                <input name="month_cycle" type="month" class="form-control" value="{{ $monthCycle }}" required>
            </div>
            <div class="col-lg-3 col-md-4 d-grid">
                <button class="btn btn-danger" type="submit">Generate &amp; Lock</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="section-heading mb-3">
            <div>
                <h5 class="mb-1">Billing</h5>
            </div>
        </div>
        @if($billingMonths->count())
            <div class="d-flex flex-wrap gap-2">
                @foreach($billingMonths as $m)
                    @php($closure = $monthClosures->get($m))
                    <span class="badge rounded-pill text-bg-light px-3 py-2 border">{{ $m }}@if($closure) · {{ $closure->status }}@endif</span>
                @endforeach
            </div>
        @else
            <div class="empty-state">No generated month found.</div>
        @endif
    </div>
</div>


<div class="card shadow-sm mb-4 border border-warning">
    <div class="card-body">
        <div class="section-heading mb-3">
            <div>
                <h5 class="mb-1">Mess-wise Bulk Rate Correction</h5>
                <div class="text-muted small">Use this only when a posted bill month was generated with wrong daily rate.</div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.billing.bulk-rate-correction') }}" class="row g-3 align-items-end">
            @csrf

            <div class="col-lg-2 col-md-4">
                <label class="form-label">Month</label>
                <select name="month_cycle" class="form-select" required>
                    @foreach($billingMonths as $m)
                        <option value="{{ $m }}" @selected($monthCycle === $m)>{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-3 col-md-4">
                <label class="form-label">Mess</label>
                <select name="mess_id" class="form-select" required>
                    <option value="">Select Mess</option>
                    @foreach(($messes ?? collect()) as $mess)
                        <option value="{{ $mess->id }}">{{ $mess->name }} @if($mess->code) ({{ $mess->code }}) @endif</option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-2 col-md-4">
                <label class="form-label">Old Rate</label>
                <input type="number" step="0.01" min="0" name="old_rate" class="form-control" placeholder="339.50" required>
            </div>

            <div class="col-lg-2 col-md-4">
                <label class="form-label">New Rate</label>
                <input type="number" step="0.01" min="0" name="new_rate" class="form-control" placeholder="325.00" required>
            </div>

            <div class="col-lg-3 col-md-8">
                <label class="form-label">Reason</label>
                <input type="text" name="reason" class="form-control" maxlength="1000" placeholder="Rate correction reason" required>
            </div>

            <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-warning" onclick="return confirm('This will correct posted bills and post ledger adjustments for selected mess only. Continue?')">
                    Post Bulk Correction
                </button>
            </div>
        </form>
    </div>
</div>


<div class="card shadow-sm mb-4 border border-primary">
    <div class="card-body">
        <div class="section-heading mb-3">
            <div>
                <h5 class="mb-1">Bulk Due Date Update</h5>
                <div class="text-muted small">Apply due date to selected month bills in one step.</div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.billing.due-date.bulk') }}" class="row g-3 align-items-end">
            @csrf

            <div class="col-lg-3 col-md-4">
                <label class="form-label">Month</label>
                <select name="month_cycle" class="form-select" required>
                    @foreach($billingMonths as $m)
                        <option value="{{ $m }}" @selected($monthCycle === $m)>{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-3 col-md-4">
                <label class="form-label">Due Date</label>
                <input type="date" name="due_date" class="form-control" required>
            </div>

            <div class="col-lg-3 col-md-4">
                <label class="form-label">Minimum Amount</label>
                <input type="number" step="0.01" min="0" name="minimum_amount" class="form-control" value="500">
            </div>

            <div class="col-lg-3 col-md-12">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="only_above_amount" id="only_above_amount" value="1" checked>
                    <label class="form-check-label" for="only_above_amount">
                        Only bills above this amount
                    </label>
                </div>
                <button type="submit" class="btn btn-primary w-100" onclick="return confirm('Apply due date to selected bills?')">Apply Due Date</button>
            </div>
        </form>
    </div>
</div>

@if($isSuperAdmin)
    <div class="row g-4 mb-2">
        <div class="col-xl-6">
            <div class="card shadow-sm border border-warning h-100">
                <div class="card-body">
                    <div class="section-heading mb-3">
                        <div>
                            <h5 class="mb-1">Billing</h5>
                        </div>
                    </div>
                    @if($billingMonths->count())
                        <form method="POST" action="{{ route('admin.month.reopen') }}" class="row g-3 align-items-end">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label">Month</label>
                                <select name="month_cycle" class="form-select" required>
                                    @foreach($billingMonths as $m)
                                        <option value="{{ $m }}">{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Reason (required)</label>
                                <input class="form-control" type="text" name="reason" required placeholder="Why reopen this month?">
                            </div>
                            <div class="col-12 d-grid d-md-flex justify-content-md-end">
                                <button class="btn btn-warning" type="submit">Reopen Month</button>
                            </div>
                        </form>
                    @else
                        <div class="empty-state">No generated month available for reopen.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card shadow-sm border border-danger h-100">
                <div class="card-body">
                    <div class="section-heading mb-3">
                        <div>
                            <h5 class="mb-1 text-danger">Billing</h5>
                        </div>
                    </div>
                    @if($billingMonths->count())
                        <form method="POST" action="{{ route('admin.month.hard-reset') }}" class="row g-3 align-items-end">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label">Month</label>
                                <select name="month_cycle" id="hard_reset_month" class="form-select" required>
                                    @foreach($billingMonths as $m)
                                        <option value="{{ $m }}">{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Confirm Text</label>
                                <input class="form-control" name="confirm_text" required placeholder="RESET-2026-01">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Reason (required)</label>
                                <input class="form-control" type="text" name="reason" required placeholder="Why hard reset?">
                            </div>
                            <div class="col-12 d-grid d-md-flex justify-content-md-end">
                                <button class="btn btn-danger" type="submit" onclick="return confirm('HARD RESET will delete month billing/ledger scope. Continue?')">HARD RESET MONTH</button>
                            </div>
                        </form>
                    @else
                        <div class="empty-state">No generated month available for hard reset.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-lg-6">
                <div class="section-heading mb-0">
                    <div>
                        <h5 class="mb-1">Billing</h5>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <form method="GET" class="row g-2 align-items-end justify-content-lg-end">
                    <div class="col-md-8">
                        <label class="form-label">Filter month</label>
                        <select name="month_cycle" class="form-select">
                            <option value="">All</option>
                            @foreach($billingMonths as $m)
                                <option value="{{ $m }}" @selected($monthCycle === $m)>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-grid">
                        <button class="btn btn-outline-primary">Apply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Billing List</span>
        <span class="badge text-bg-light border">{{ $billingRows->count() }} rows</span>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle table-hover">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Due Date</th>
                    <th>Member</th>
                    <th>Days</th>
                    <th>Rate</th>
                    <th>Base</th>
                    <th>Extras</th>
                    <th>Net</th>
                    <th>Locked</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($billingRows as $r)
                <tr>
                    <td><span class="badge text-bg-light border">{{ $r->month_cycle }}</span></td>
                    <td style="min-width: 180px;">
                        <form method="POST" action="{{ route('admin.billing.due-date', $r) }}" class="d-flex gap-1 align-items-center">
                            @csrf
                            <input type="date" name="due_date" class="form-control form-control-sm" value="{{ optional($r->due_date)->format('Y-m-d') }}">
                            <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                        </form>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">{{ $r->member->member_code ?? '-' }}</div>
                        <div class="text-muted small">{{ $r->member->name ?? '-' }}</div>
                    </td>
                    <td>{{ $r->active_days }}</td>
                    <td>{{ number_format((float) $r->rate_per_day, 2) }}</td>
                    <td>{{ number_format((float) $r->base_amount, 2) }}</td>
                    <td>{{ number_format((float) $r->extras_amount, 2) }}</td>
                    <td class="fw-semibold">{{ number_format((float) $r->net_payable, 2) }}</td>
                    <td><span class="badge {{ $r->is_locked ? 'bg-success' : 'bg-warning text-dark' }}">{{ $r->is_locked ? 'Yes' : 'No' }}</span></td>
                    <td class="text-end">
                        @if($r->billing_status === 'POSTED')
                            <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="collapse" data-bs-target="#correct-billing-{{ $r->id }}">
                                Correct Bill
                            </button>
                        @else
                            <span class="text-muted small">Not posted</span>
                        @endif
                    </td>
                </tr>
                <tr class="collapse" id="correct-billing-{{ $r->id }}">
                    <td colspan="10">
                        <form method="POST" action="{{ route('admin.billing.correct', $r) }}" class="row g-2 align-items-end bg-light border rounded p-3">
                            @csrf
                            <div class="col-md-3">
                                <label class="form-label">Current Net</label>
                                <input type="text" class="form-control" value="{{ number_format((float) $r->net_payable, 2) }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">New Net Payable</label>
                                <input type="number" step="0.01" min="0" name="new_net_payable" class="form-control" value="{{ number_format((float) $r->net_payable, 2, '.', '') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Reason</label>
                                <input type="text" name="reason" class="form-control" placeholder="Correction reason" required maxlength="1000">
                            </div>
                            <div class="col-md-2 d-grid">
                                <button type="submit" class="btn btn-warning" onclick="return confirm('This will post ledger correction. Continue?')">Post</button>
                            </div>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">No billing rows found for the selected filter.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
