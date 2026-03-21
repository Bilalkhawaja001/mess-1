@extends('layouts.app')

@section('title', 'Overall Recovery')
@section('page_title', 'Overall Member Balances')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="ALL" @selected($statusFilter === 'ALL')>All</option>
                    <option value="DUE" @selected($statusFilter === 'DUE')>Due</option>
                    <option value="CLEAR" @selected($statusFilter === 'CLEAR')>Clear</option>
                    <option value="ADVANCE" @selected($statusFilter === 'ADVANCE')>Advance</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Member Search</label>
                <input class="form-control" type="text" name="q" value="{{ $q }}" placeholder="Member Code or Name">
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-primary">View</button>
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-success" name="export" value="csv">Export CSV</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-2"><div class="border rounded p-2 bg-white"><div class="small text-muted">Members</div><div class="fw-bold text-end">{{ $totals['members'] }}</div></div></div>
    <div class="col-md-2"><div class="border rounded p-2 bg-white"><div class="small text-muted">Due</div><div class="fw-bold text-end">{{ $totals['due_count'] }}</div></div></div>
    <div class="col-md-2"><div class="border rounded p-2 bg-white"><div class="small text-muted">Clear</div><div class="fw-bold text-end">{{ $totals['clear_count'] }}</div></div></div>
    <div class="col-md-2"><div class="border rounded p-2 bg-white"><div class="small text-muted">Advance</div><div class="fw-bold text-end">{{ $totals['advance_count'] }}</div></div></div>
    <div class="col-md-2"><div class="border rounded p-2 bg-white"><div class="small text-muted">Total Debit</div><div class="fw-bold text-end">{{ number_format($totals['total_debit'], 2) }}</div></div></div>
    <div class="col-md-2"><div class="border rounded p-2 bg-white"><div class="small text-muted">Total Credit</div><div class="fw-bold text-end">{{ number_format($totals['total_credit'], 2) }}</div></div></div>
</div>

<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Member ID</th>
                    <th>Member Name</th>
                    <th>Department</th>
                    <th class="text-end">Total Debit</th>
                    <th class="text-end">Total Credit</th>
                    <th class="text-end">Closing Balance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>{{ $r['member_id'] }}</td>
                        <td>{{ $r['member_name'] }}</td>
                        <td>{{ $r['department'] }}</td>
                        <td class="text-end">{{ number_format($r['total_debit'], 2) }}</td>
                        <td class="text-end">{{ number_format($r['total_credit'], 2) }}</td>
                        <td class="text-end">{{ number_format($r['closing_balance'], 2) }}</td>
                        <td>
                            @if($r['status'] === 'Due')
                                <span class="badge text-bg-warning">Due</span>
                            @elseif($r['status'] === 'Clear')
                                <span class="badge text-bg-success">Clear</span>
                            @else
                                <span class="badge text-bg-info">Advance</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-muted">No rows found.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3">Totals</th>
                    <th class="text-end">{{ number_format($totals['total_debit'], 2) }}</th>
                    <th class="text-end">{{ number_format($totals['total_credit'], 2) }}</th>
                    <th class="text-end">{{ number_format($totals['total_closing'], 2) }}</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
