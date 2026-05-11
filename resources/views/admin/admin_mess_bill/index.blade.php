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

<div class="card shadow-sm mb-4">
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
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <tbody>
                    <tr>
                        <th style="width:45%;">Purchase Report Total</th>
                        <td class="text-end">{{ number_format($purchaseTotal, 2) }}</td>
                    </tr>
                    <tr>
                        <th>Total Attendance</th>
                        <td class="text-end">{{ number_format($totalAttendance) }}</td>
                    </tr>
                    <tr>
                        <th>Per Attendance Expense</th>
                        <td class="text-end">{{ number_format($perAttendanceExpense, 6) }}</td>
                    </tr>
                    <tr class="table-dark fw-bold">
                        <th>Total Expenses</th>
                        <td class="text-end">{{ number_format($totalExpenses, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="text-muted small mt-3">
            Contractor share is deducted from purchase total in backend and is not shown on bill.
        </div>
    </div>
</div>
@endsection
