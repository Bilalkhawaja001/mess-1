@extends('layouts.app')

@section('title', 'Bill Publish')
@section('page_title', 'Bill Publish')

@section('content')
<div class="page-hero page-hero-compact mb-4">
    <div>
        <h1 class="page-hero-title">Bill Publish</h1>
        <div class="text-muted small">Manually publish verified bills and notify selected members with their own bill amount.</div>
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
    <form method="POST" action="{{ route('admin.bill-publish.store') }}" id="bill-publish-form">
        @csrf
        <input type="hidden" name="month_cycle" value="{{ $monthCycle }}">

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="fw-semibold">Selected Month Bills</span>
                <span class="text-muted small ms-2">
                    Selected: <span id="selected-bills-count">{{ $bills->count() }}</span> / {{ $bills->count() }}
                </span>
            </div>

            @if($monthCycle && $summary['bill_count'] > 0)
                <button class="btn btn-success" type="submit" onclick="return confirm('Publish selected bills and send amount notification to selected members for {{ $monthCycle }}?')">
                    Publish Selected & Notify
                </button>
            @endif
        </div>

        <div class="card-body table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th style="width:40px;">
                            <input type="checkbox" class="form-check-input" id="select-all-bills" checked>
                        </th>
                        <th>Member Code</th>
                        <th>Name</th>
                        <th>Month</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bills as $bill)
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input bill-row-check" name="bill_ids[]" value="{{ $bill->id }}" checked>
                            </td>
                            <td>{{ $bill->member?->member_code ?? '-' }}</td>
                            <td>{{ $bill->member?->name ?? '-' }}</td>
                            <td>{{ $bill->month_cycle }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $bill->net_payable, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No bills found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>
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

@push('scripts')
<script>
(() => {
    const selectAll = document.getElementById('select-all-bills');
    const checks = Array.from(document.querySelectorAll('.bill-row-check'));
    const selectedCount = document.getElementById('selected-bills-count');
    const form = document.getElementById('bill-publish-form');

    const sync = () => {
        const selected = checks.filter((check) => check.checked).length;

        if (selectedCount) {
            selectedCount.textContent = selected;
        }

        if (selectAll) {
            selectAll.checked = checks.length > 0 && selected === checks.length;
            selectAll.indeterminate = selected > 0 && selected < checks.length;
        }
    };

    selectAll?.addEventListener('change', () => {
        checks.forEach((check) => {
            check.checked = selectAll.checked;
        });
        sync();
    });

    checks.forEach((check) => check.addEventListener('change', sync));

    form?.addEventListener('submit', (event) => {
        const selected = checks.filter((check) => check.checked).length;

        if (selected === 0) {
            event.preventDefault();
            alert('Select at least one member bill before publishing.');
        }
    });

    sync();
})();
</script>
@endpush
@endsection
