@extends('layouts.app')

@section('title', 'Admin Mess Bill')
@section('page_title', 'Admin Mess Bill')

@section('content')
<style>
    .amb-shell {
        max-width: 1180px;
        margin: 0 auto;
    }

    .amb-hero {
        border: 1px solid rgba(148, 163, 184, .22);
        background:
            radial-gradient(circle at top left, rgba(59, 130, 246, .14), transparent 34%),
            linear-gradient(135deg, #ffffff 0%, #f8fbff 55%, #eef6ff 100%);
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
    }

    .amb-title {
        font-size: 30px;
        font-weight: 800;
        letter-spacing: -.04em;
        color: #0f172a;
        margin: 0;
    }

    .amb-subtitle {
        color: #64748b;
        font-size: 14px;
        margin-top: 6px;
    }

    .amb-filter-card,
    .amb-invoice-card,
    .amb-kpi-card {
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 22px;
        background: #fff;
        box-shadow: 0 16px 38px rgba(15, 23, 42, .07);
    }

    .amb-filter-card {
        padding: 20px;
    }

    .amb-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    .amb-kpi-card {
        padding: 18px;
        min-height: 118px;
        position: relative;
        overflow: hidden;
    }

    .amb-kpi-card::after {
        content: "";
        position: absolute;
        width: 130px;
        height: 130px;
        right: -58px;
        top: -58px;
        border-radius: 999px;
        background: rgba(59, 130, 246, .10);
    }

    .amb-kpi-label {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 10px;
    }

    .amb-kpi-value {
        color: #0f172a;
        font-size: 23px;
        font-weight: 800;
        letter-spacing: -.03em;
        line-height: 1.1;
    }

    .amb-kpi-note {
        color: #94a3b8;
        font-size: 12px;
        margin-top: 8px;
    }

    .amb-invoice-card {
        overflow: hidden;
    }

    .amb-invoice-header {
        padding: 26px 30px;
        background: linear-gradient(135deg, #0f172a, #1e3a8a);
        color: #fff;
        display: flex;
        justify-content: space-between;
        gap: 20px;
        align-items: center;
        flex-wrap: wrap;
    }

    .amb-invoice-title {
        font-size: 26px;
        font-weight: 850;
        letter-spacing: .08em;
        margin: 0;
    }

    .amb-cycle-pill {
        border: 1px solid rgba(255,255,255,.22);
        background: rgba(255,255,255,.10);
        color: #dbeafe;
        border-radius: 999px;
        padding: 9px 14px;
        font-size: 13px;
        font-weight: 700;
    }

    .amb-invoice-body {
        padding: 26px 30px 30px;
    }

    .amb-table {
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
        overflow: hidden;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
    }

    .amb-table th,
    .amb-table td {
        padding: 16px 18px;
        vertical-align: middle;
        border-color: #e2e8f0 !important;
    }

    .amb-table th {
        color: #0f172a;
        font-weight: 750;
        background: #f8fafc;
    }

    .amb-table td {
        font-weight: 700;
        color: #0f172a;
        background: #fff;
    }

    .amb-row-soft th,
    .amb-row-soft td {
        background: #f1f5f9 !important;
    }

    .amb-row-total th,
    .amb-row-total td {
        background: #111827 !important;
        color: #fff !important;
        font-size: 18px;
    }

    .amb-helper {
        margin-top: 18px;
        color: #64748b;
        font-size: 13px;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        padding: 12px 14px;
    }

    .print-only {
        display: none;
    }

    /* PRINT INVOICE */
    .print-invoice {
        color: #111827;
        font-family: Arial, Helvetica, sans-serif;
    }

    .print-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        border-bottom: 2px solid #111827;
        padding-bottom: 14px;
        margin-bottom: 18px;
    }

    .print-brand h1 {
        margin: 0;
        font-size: 26px;
        font-weight: 800;
        letter-spacing: .08em;
    }

    .print-brand .sub {
        margin-top: 4px;
        color: #475569;
        font-size: 12px;
    }

    .print-meta {
        text-align: right;
        font-size: 12px;
        color: #334155;
    }

    .print-meta strong {
        color: #111827;
    }

    .print-bill-title {
        text-align: center;
        margin: 10px 0 18px;
    }

    .print-bill-title h2 {
        margin: 0;
        font-size: 22px;
        letter-spacing: .06em;
        font-weight: 800;
    }

    .print-bill-title .sub {
        margin-top: 5px;
        font-size: 12px;
        color: #475569;
    }

    .print-summary {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 18px;
    }

    .print-summary td {
        border: 1px solid #cbd5e1;
        padding: 10px 12px;
        font-size: 12px;
    }

    .print-summary td.label {
        background: #f8fafc;
        font-weight: 700;
        width: 32%;
    }

    .print-summary td.value {
        text-align: right;
        font-weight: 700;
    }

    .print-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .print-table th,
    .print-table td {
        border: 1px solid #cbd5e1;
        padding: 12px 14px;
        font-size: 13px;
    }

    .print-table th {
        background: #f1f5f9;
        text-align: left;
        font-weight: 800;
    }

    .print-table td:last-child,
    .print-table th:last-child {
        text-align: right;
    }

    .print-table .grand-row td {
        background: #000 !important;
        color: #fff !important;
        font-size: 17px;
        font-weight: 900;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .print-footer {
        margin-top: 230px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
    }

    .print-sign-box {
        padding-top: 16px;
        border-top: 1px solid #111827;
        text-align: center;
        font-size: 12px;
        color: #334155;
    }

    @media (max-width: 991.98px) {
        .amb-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .amb-hero,
        .amb-filter-card,
        .amb-invoice-body {
            padding: 18px;
        }

        .amb-kpi-grid {
            grid-template-columns: 1fr;
        }

        .amb-title {
            font-size: 24px;
        }

        .amb-table th,
        .amb-table td {
            padding: 13px 12px;
            font-size: 13px;
        }
    }

    @page {
        size: A4 portrait;
        margin: 12mm;
    }

    @media print {
        body {
            background: #fff !important;
        }

        .no-print,
        .sidebar,
        .topbar,
        .page-hero,
        .amb-shell > .amb-hero,
        .amb-shell > .amb-filter-card,
        .amb-shell > .amb-kpi-grid,
        .amb-shell > .amb-invoice-card {
            display: none !important;
        }

        .content-wrap,
        .page-body,
        .page-container,
        .amb-shell {
            margin: 0 !important;
            padding: 0 !important;
            max-width: 100% !important;
            width: 100% !important;
        }

        .print-only {
            display: block !important;
        }

        .print-invoice {
            display: block !important;
        }
    }
</style>

<div class="amb-shell">
    <div class="amb-hero mb-4">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h1 class="amb-title">Admin Mess Bill</h1>
                <div class="amb-subtitle">
                    Cycle {{ $monthCycle }} · {{ $rangeStart->format('d M Y') }} to {{ $rangeEnd->format('d M Y') }}
                </div>
            </div>
            <div class="amb-cycle-pill">
                {{ $monthCycle }}
            </div>
        </div>
    </div>

    <div class="amb-filter-card mb-4 no-print">
        <form method="GET" action="{{ route('admin.admin-mess-bill.index') }}" class="row g-3 align-items-end">
            <div class="col-lg-4 col-md-5">
                <label class="form-label fw-semibold">Select Month</label>
                <input type="month" name="month_cycle" class="form-control form-control-lg" value="{{ $monthCycle }}" required>
            </div>
            <div class="col-lg-3 col-md-3 d-grid">
                <button class="btn btn-primary btn-lg" type="submit">Apply</button>
            </div>
            <div class="col-lg-3 col-md-3 d-grid">
                <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-lg">Print Bill</button>
            </div>
        </form>
    </div>

    <div class="amb-kpi-grid mb-4 no-print">
        <div class="amb-kpi-card">
            <div class="amb-kpi-label">Bill Total Expenses</div>
            <div class="amb-kpi-value">{{ number_format($totalExpenses, 2) }}</div>
            <div class="amb-kpi-note">After contractor share deduction</div>
        </div>
        <div class="amb-kpi-card">
            <div class="amb-kpi-label">Guest Amount</div>
            <div class="amb-kpi-value">{{ number_format($guestAmount, 2) }}</div>
            <div class="amb-kpi-note">Approved guest meals</div>
        </div>
        <div class="amb-kpi-card">
            <div class="amb-kpi-label">Balance Amount</div>
            <div class="amb-kpi-value">{{ number_format($balanceAmount, 2) }}</div>
            <div class="amb-kpi-note">Total minus guest amount</div>
        </div>
        <div class="amb-kpi-card">
            <div class="amb-kpi-label">Total Amount</div>
            <div class="amb-kpi-value">{{ number_format($totalAmount, 2) }}</div>
            <div class="amb-kpi-note">Company share + guest amount</div>
        </div>
    </div>

    <div class="amb-invoice-card">
        <div class="amb-invoice-header">
            <div>
                <h2 class="amb-invoice-title">ADMIN MESS BILL</h2>
                <div class="mt-1 opacity-75">{{ $rangeStart->format('d M Y') }} to {{ $rangeEnd->format('d M Y') }}</div>
            </div>
            <div class="amb-cycle-pill">
                Cycle {{ $monthCycle }}
            </div>
        </div>

        <div class="amb-invoice-body">
            <div class="table-responsive">
                <table class="table amb-table align-middle">
                    <tbody>
                        <tr>
                            <th style="width: 58%;">Total Expenses</th>
                            <td class="text-end">{{ number_format($totalExpenses, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Guest Amount</th>
                            <td class="text-end">{{ number_format($guestAmount, 2) }}</td>
                        </tr>
                        <tr class="amb-row-soft">
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
                        <tr class="amb-row-total">
                            <th>Total Amount to be Paid</th>
                            <td class="text-end">{{ number_format($totalAmount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="amb-helper no-print">
                Backend calculation remains unchanged: contractor attendance share is deducted from purchase report total before this bill is displayed.
            </div>
        </div>
    </div>

    <div class="print-only print-invoice">
        <div class="print-header">
            <div class="print-brand">
                <h1>ADMIN MESS</h1>
                
            </div>
            <div class="print-meta">
                <div><strong>Bill Cycle:</strong> {{ $monthCycle }}</div>
                <div><strong>Period:</strong> {{ $rangeStart->format('d M Y') }} - {{ $rangeEnd->format('d M Y') }}</div>
                <div><strong>Printed On:</strong> {{ now()->format('d M Y h:i A') }}</div>
            </div>
        </div>

        <div class="print-bill-title">
            <h2>ADMIN MESS</h2>
            <div class="sub">Monthly admin mess statement</div>
        </div>

        <table class="print-summary">
            <tr>
                <td class="label">Document Type</td>
                <td>Bill Statement</td>
                <td class="label">Month Cycle</td>
                <td>{{ $monthCycle }}</td>
            </tr>
            <tr>
                <td class="label">Billing Period</td>
                <td>{{ $rangeStart->format('d M Y') }} to {{ $rangeEnd->format('d M Y') }}</td>
                <td class="label">Document Type</td>
                <td>Bill Statement</td>
            </tr>
        </table>

        <table class="print-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount (PKR)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Expenses</td>
                    <td>{{ number_format($totalExpenses, 2) }}</td>
                </tr>
                <tr>
                    <td>Guest Amount</td>
                    <td>{{ number_format($guestAmount, 2) }}</td>
                </tr>
                <tr>
                    <td>Balance Amount</td>
                    <td>{{ number_format($balanceAmount, 2) }}</td>
                </tr>
                <tr>
                    <td>50% Amount Paid by Company</td>
                    <td>{{ number_format($companyPaid, 2) }}</td>
                </tr>
                <tr>
                    <td>Guest Amount</td>
                    <td>{{ number_format($guestAmount, 2) }}</td>
                </tr>
                <tr class="grand-row">
                    <td>Total Amount to be Paid</td>
                    <td>{{ number_format($totalAmount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="print-footer">
            <div class="print-sign-box">
                Prepared By
            </div>
            <div class="print-sign-box">
                Authorized Signature
            </div>
        </div>
    </div>
</div>
@endsection
