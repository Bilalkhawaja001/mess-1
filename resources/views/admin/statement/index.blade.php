@extends('layouts.app')
@section('title','Statement')
@section('page_title','Statement')
@push('styles')
<style>
    @media print {
        .no-print { display:none !important; }
        body { background:#fff; }
        .card { border:0; box-shadow:none !important; }
    }
</style>
@endpush
@section('content')
<div class="card shadow-sm mb-3 no-print">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Member Search</label>
                <input name="member_q" class="form-control" value="{{ $memberQuery }}" placeholder="code, name, department">
            </div>
            <div class="col-md-3">
                <label class="form-label">Member</label>
                <select name="member_id" class="form-select">
                    <option value="">All</option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}" @selected((string) $memberId === (string) $member->id)>{{ $member->member_code }} - {{ $member->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Month</label>
                <input name="month_cycle" class="form-control" value="{{ $monthCycle }}" placeholder="2026-03">
            </div>
            <div class="col-md-2">
                <label class="form-label">From Month</label>
                <input name="from_month" class="form-control" value="{{ $fromMonth }}" placeholder="2026-01">
            </div>
            <div class="col-md-2">
                <label class="form-label">To Month</label>
                <input name="to_month" class="form-control" value="{{ $toMonth }}" placeholder="2026-03">
            </div>
            <div class="col-md-2">
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-outline-primary" type="submit">Load</button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.statement.index', array_filter(['member_id' => $memberId, 'member_q' => $memberQuery, 'month_cycle' => $monthCycle, 'from_month' => $fromMonth, 'to_month' => $toMonth, 'from_date' => $fromDate, 'to_date' => $toDate, 'export' => 'csv'])) }}">Download CSV</a>
                <button type="button" onclick="window.print()" class="btn btn-primary">Print</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Opening Balance</div><div class="fs-5 fw-semibold">{{ number_format((float) ($totals['opening_balance'] ?? 0), 2) }}</div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Bill Debit</div><div class="fs-5 fw-semibold">{{ number_format((float) ($totals['debit'] ?? 0), 2) }}</div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Payment Credit</div><div class="fs-5 fw-semibold">{{ number_format((float) ($totals['credit'] ?? 0), 2) }}</div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Closing Balance</div><div class="fs-5 fw-semibold">{{ number_format((float) ($totals['closing_balance'] ?? 0), 2) }}</div></div></div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Member Statement</div>
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Month</th>
                    <th>Member</th>
                    <th>Ref</th>
                    <th>Reason</th>
                    <th>Debit</th>
                    <th>Credit</th>
                    <th>Running Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr class="table-light">
                    <td colspan="7" class="fw-semibold">Opening Balance</td>
                    <td class="fw-semibold">{{ number_format((float) ($totals['opening_balance'] ?? 0), 2) }}</td>
                </tr>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ optional($row['date'])->format('Y-m-d') }}</td>
                        <td>{{ $row['month_cycle'] }}</td>
                        <td>{{ $row['member_code'] }} @if(!empty($row['member_name']))<div class="small text-muted">{{ $row['member_name'] }}</div>@endif</td>
                        <td>{{ $row['ref_type'] }}#{{ $row['ref_id'] }}</td>
                        <td>{{ $row['reason_code'] ?: '-' }}</td>
                        <td>{{ number_format((float) $row['debit'], 2) }}</td>
                        <td>{{ number_format((float) $row['credit'], 2) }}</td>
                        <td>{{ number_format((float) $row['balance_after'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No statement rows found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
