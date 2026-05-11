@extends('layouts.app')

@section('title', 'Admin Mess Bill')
@section('page_title', 'Admin Mess Bill')

@section('content')
<div class="page-hero page-hero-compact mb-4">
    <div>
        <h1 class="page-hero-title">Admin Mess Bill</h1>
        <div class="text-muted small">
            Cycle {{ $monthCycle }} · {{ $rangeStart->format('d M Y') }} to {{ $rangeEnd->format('d M Y') }}
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4 no-print">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.admin-mess-bill.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Select Month</label>
                <input type="month" name="month_cycle" class="form-control" value="{{ $monthCycle }}" required>
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-primary" type="submit">Apply</button>
            </div>
            <div class="col-md-3 d-grid">
                <button type="button" onclick="window.print()" class="btn btn-outline-dark">Print</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="text-center mb-4">
            <h4 class="fw-bold mb-1">ADMIN MESS BILL</h4>
            <div class="text-muted">{{ $rangeStart->format('d M Y') }} to {{ $rangeEnd->format('d M Y') }}</div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <tbody>
                    <tr>
                        <th style="width: 55%;">Total Expenses</th>
                        <td class="text-end fw-semibold">{{ number_format($totalExpenses, 2) }}</td>
                    </tr>
                    <tr>
                        <th>Guest Amount</th>
                        <td class="text-end">{{ number_format($guestAmount, 2) }}</td>
                    </tr>
                    <tr class="table-light fw-bold">
                        <th>Balance Amount</th>
                        <td class="text-end">{{ number_format($balanceAmount, 2) }}</td>
                    </tr>
                    <tr>
                        <th>50% Amount Paid by Company</th>
                        <td class="text-end">{{ number_format($companyPaid, 2) }}</td>
                    </tr>
                    <tr>
                        <th>Guest Amount</th>
                        <td class="text-end">{{ number_format($guestAmount, 2) }}</td>
                    </tr>
                    <tr class="table-dark fw-bold">
                        <th>Total Amount</th>
                        <td class="text-end">{{ number_format($totalAmount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="text-muted small mt-3 no-print">
            Backend: Purchase report total is divided by total attendance. Contractor attendance share is deducted before bill display.
        </div>
    </div>
</div>

<style>
@media print {
    .no-print,
    .sidebar,
    .topbar,
    .page-hero {
        display: none !important;
    }

    .content-wrap,
    .page-body,
    .page-container {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    .card {
        border: 0 !important;
        box-shadow: none !important;
    }
}
</style>
@endsection
