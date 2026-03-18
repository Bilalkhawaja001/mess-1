@extends('layouts.app')

@section('title', 'Billing')
@section('page_title', 'Billing')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-header">Generate Billing (Month Cycle)</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.billing.generate') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Month Cycle (YYYY-MM)</label>
                <input name="month_cycle" class="form-control" placeholder="2026-03" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Rate per day</label>
                <input name="rate_per_day" type="number" step="0.01" class="form-control" value="100">
            </div>
            <div class="col-md-2"><button class="btn btn-primary">Generate</button></div>
        </form>
    </div>
</div>

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
            <div class="col-md-2"><button class="btn btn-outline-primary">Apply</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Billing List</div>
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Month</th><th>Member</th><th>Days</th><th>Rate</th><th>Base</th><th>Extras</th><th>Net</th><th>Locked</th></tr></thead>
            <tbody>
            @foreach($rows as $r)
                <tr>
                    <td>{{ $r->month_cycle }}</td>
                    <td>{{ $r->member->member_code ?? '-' }} - {{ $r->member->name ?? '-' }}</td>
                    <td>{{ $r->active_days }}</td>
                    <td>{{ number_format((float)$r->rate_per_day,2) }}</td>
                    <td>{{ number_format((float)$r->base_amount,2) }}</td>
                    <td>{{ number_format((float)$r->extras_amount,2) }}</td>
                    <td>{{ number_format((float)$r->net_payable,2) }}</td>
                    <td><span class="badge {{ $r->is_locked ? 'bg-success' : 'bg-warning text-dark' }}">{{ $r->is_locked ? 'Yes' : 'No' }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
