@extends('layouts.app')

@section('title', 'Bill Publish')
@section('page_title', 'Bill Publish')

@section('content')
<div class="page-hero page-hero-compact mb-4">
    <div>
        <h1 class="page-hero-title">Bill Publish</h1>
        <div class="text-muted small">Manually publish verified bills and notify each member with their own bill amount.</div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.bill-publish.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Month</label>
                <select name="month_cycle" class="form-select" required>
                    @foreach($months as $month)
                        <option value="{{ $month }}" @selected($monthCycle === $month)>{{ $month }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-primary" type="submit">Load Bills</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Bill Count</div>
                <div class="fs-3 fw-bold">{{ number_format($summary['bill_count']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Bill Amount</div>
                <div class="fs-3 fw-bold">{{ number_format($summary['total_bill_amount'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Members With Device Tokens</div>
                <div class="fs-3 fw-bold">{{ number_format($summary['members_with_tokens']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>Selected Month Bills</span>
        @if($monthCycle && $summary['bill_count'] > 0)
            <form method="POST" action="{{ route('admin.bill-publish.store') }}">
                @csrf
                <input type="hidden" name="month_cycle" value="{{ $monthCycle }}">
                <button class="btn btn-success" type="submit" onclick="return confirm('Publish bills and send amount notification to members for {{ $monthCycle }}?')">
                    Publish & Notify Members
                </button>
            </form>
        @endif
    </div>
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Member Code</th>
                    <th>Name</th>
                    <th>Month</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bills->take(100) as $bill)
                    <tr>
                        <td>{{ $bill->member?->member_code ?? '-' }}</td>
                        <td>{{ $bill->member?->name ?? '-' }}</td>
                        <td>{{ $bill->month_cycle }}</td>
                        <td class="text-end fw-semibold">{{ number_format((float) $bill->net_payable, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">No bills found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($bills->count() > 100)
            <div class="text-muted small">Showing first 100 bills only. Notifications will be sent to all {{ $bills->count() }} bills.</div>
        @endif
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Publish History</div>
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Month</th>
                    <th>Bills</th>
                    <th>Total Amount</th>
                    <th>Tokens</th>
                    <th>Success</th>
                    <th>Failed</th>
                    <th>Published By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($runs as $run)
                    <tr>
                        <td>{{ optional($run->published_at)->format('Y-m-d H:i') }}</td>
                        <td>{{ $run->month_cycle }}</td>
                        <td>{{ $run->bill_count }}</td>
                        <td>{{ number_format((float) $run->total_bill_amount, 2) }}</td>
                        <td>{{ $run->total_tokens }}</td>
                        <td class="text-success fw-semibold">{{ $run->success_count }}</td>
                        <td class="text-danger fw-semibold">{{ $run->failed_count }}</td>
                        <td>{{ $run->publisher?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No publish history yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
