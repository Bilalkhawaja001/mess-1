@extends('layouts.app')

@section('title', 'Billing')
@section('page_title', 'Generate Monthly Bill')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="mb-3">
            <h5 class="mb-1">Generate Monthly Bill</h5>
            <div class="text-muted">Generate, lock, and manage closed/reopened billing months.</div>
        </div>

        <form method="POST" action="{{ route('admin.billing.generate') }}" class="row g-2 align-items-end mb-0">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Month</label>
                <input name="month_cycle" type="month" class="form-control" value="{{ $monthCycle }}" required>
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-danger" type="submit">Generate &amp; Lock</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h6 class="mb-2">Generated Months</h6>
        @if($months->count())
            <div class="d-flex flex-wrap gap-2">
                @foreach($months as $m)
                    @php($closure = $monthClosures->get($m))
                    <span class="badge text-bg-light px-3 py-2">{{ $m }}@if($closure) · {{ $closure->status }}@endif</span>
                @endforeach
            </div>
        @else
            <div class="text-secondary">No generated month found.</div>
        @endif
    </div>
</div>

@if($isSuperAdmin)
    <div class="card shadow-sm border border-warning mb-3">
        <div class="card-body">
            <h6 class="mb-2">Super Admin Override: Reopen Month</h6>
            @if($months->count())
                <form method="POST" action="{{ route('admin.month.reopen') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Month</label>
                        <select name="month_cycle" class="form-select" required>
                            @foreach($months as $m)
                                <option value="{{ $m }}">{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Reason (required)</label>
                        <input class="form-control" type="text" name="reason" required placeholder="Why reopen this month?">
                    </div>
                    <div class="col-md-3 d-grid">
                        <button class="btn btn-warning" type="submit">Reopen Month</button>
                    </div>
                </form>
            @else
                <div class="text-muted">No generated month available for reopen.</div>
            @endif
        </div>
    </div>

    <div class="card shadow-sm border border-danger mb-3">
        <div class="card-body">
            <h6 class="mb-2 text-danger">Super Admin Override: HARD RESET MONTH</h6>
            @if($months->count())
                <form method="POST" action="{{ route('admin.month.hard-reset') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Month</label>
                        <select name="month_cycle" id="hard_reset_month" class="form-select" required>
                            @foreach($months as $m)
                                <option value="{{ $m }}">{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Confirm Text (type RESET-YYYY-MM)</label>
                        <input class="form-control" name="confirm_text" required placeholder="RESET-2026-01">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reason (required)</label>
                        <input class="form-control" type="text" name="reason" required placeholder="Why hard reset?">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-danger" type="submit" onclick="return confirm('HARD RESET will delete month billing/ledger scope. Continue?')">HARD RESET MONTH</button>
                    </div>
                </form>
            @else
                <div class="text-muted">No generated month available for hard reset.</div>
            @endif
        </div>
    </div>
@endif

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Filter month</label>
                <select name="month_cycle" class="form-select">
                    <option value="">All</option>
                    @foreach($months as $m)
                        <option value="{{ $m }}" @selected($monthCycle === $m)>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-primary">Apply</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Billing List</div>
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Member</th>
                    <th>Days</th>
                    <th>Rate</th>
                    <th>Base</th>
                    <th>Extras</th>
                    <th>Net</th>
                    <th>Locked</th>
                </tr>
            </thead>
            <tbody>
            @foreach($rows as $r)
                <tr>
                    <td>{{ $r->month_cycle }}</td>
                    <td>{{ $r->member->member_code ?? '-' }} - {{ $r->member->name ?? '-' }}</td>
                    <td>{{ $r->active_days }}</td>
                    <td>{{ number_format((float) $r->rate_per_day, 2) }}</td>
                    <td>{{ number_format((float) $r->base_amount, 2) }}</td>
                    <td>{{ number_format((float) $r->extras_amount, 2) }}</td>
                    <td>{{ number_format((float) $r->net_payable, 2) }}</td>
                    <td><span class="badge {{ $r->is_locked ? 'bg-success' : 'bg-warning text-dark' }}">{{ $r->is_locked ? 'Yes' : 'No' }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
