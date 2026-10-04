@extends('layouts.app')

@section('title', 'Admin Mess Bill')
@section('page_title', 'Admin Mess Bill')

@section('content')
<style>
    .amb-shell { max-width: 1180px; margin: 0 auto; }
    .amb-hero {
        border: 1px solid rgba(148, 163, 184, .22);
        background:
            radial-gradient(circle at top left, rgba(59, 130, 246, .14), transparent 34%),
            linear-gradient(135deg, #ffffff 0%, #f8fbff 55%, #eef6ff 100%);
        border-radius: 24px; padding: 24px; box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
    }
    .amb-title { font-size: 30px; font-weight: 800; letter-spacing: -.04em; color: #0f172a; margin: 0; }
    .amb-subtitle { color: #64748b; font-size: 14px; margin-top: 6px; }
    .amb-filter-card, .amb-invoice-card, .amb-kpi-card {
        border: 1px solid rgba(148, 163, 184, .22); border-radius: 22px; background: #fff;
        box-shadow: 0 16px 38px rgba(15, 23, 42, .07);
    }
    .amb-filter-card { padding: 20px; }
    .amb-kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
    .amb-kpi-card { padding: 18px; min-height: 118px; position: relative; overflow: hidden; }
    .amb-kpi-card::after {
        content: ""; position: absolute; width: 130px; height: 130px; right: -58px; top: -58px;
        border-radius: 999px; background: rgba(59, 130, 246, .10);
    }
    .amb-kpi-label { color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 10px; }
    .amb-kpi-value { color: #0f172a; font-size: 23px; font-weight: 800; letter-spacing: -.03em; line-height: 1.1; }
    .amb-kpi-note { color: #94a3b8; font-size: 12px; margin-top: 8px; }
    .amb-invoice-card { overflow: hidden; }
    .amb-invoice-header {
        padding: 26px 30px; background: linear-gradient(135deg, #0f172a, #1e3a8a); color: #fff;
        display: flex; justify-content: space-between; gap: 20px; align-items: center; flex-wrap: wrap;
    }
    .amb-invoice-title { font-size: 26px; font-weight: 850; letter-spacing: .08em; margin: 0; }
    .amb-cycle-pill {
        border: 1px solid rgba(255,255,255,.22); background: rgba(255,255,255,.10); color: #dbeafe;
        border-radius: 999px; padding: 9px 14px; font-size: 13px; font-weight: 700;
    }
    .amb-invoice-body { padding: 26px 30px 30px; }
    .amb-table {
        margin: 0; border-collapse: separate; border-spacing: 0; overflow: hidden;
        border-radius: 16px; border: 1px solid #e2e8f0; width: 100%;
    }
    .amb-table th, .amb-table td { padding: 14px 18px; vertical-align: middle; border-color: #e2e8f0 !important; }
    .amb-table th { color: #0f172a; font-weight: 700; background: #fff; }
    .amb-table td { font-weight: 700; color: #0f172a; background: #fff; text-align: right; }
    .amb-section th {
        background: #0f172a !important; color: #fff !important; font-weight: 800;
        font-size: 12px; letter-spacing: .12em; text-transform: uppercase;
    }
    .amb-sub th, .amb-sub td { background: #eef2f7 !important; font-weight: 800; }
    .amb-less th, .amb-less td { color: #b91c1c; }
    .amb-row-total th, .amb-row-total td { background: #111827 !important; color: #fff !important; font-size: 18px; }
    .print-only { display: none; }
    .print-invoice { color: #111827; font-family: Arial, Helvetica, sans-serif; }


    /* --- new print statement layout --- */
    .print-invoice { font-family: "Inter", -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; font-size: 10pt; line-height: 1.5; color: #000; }
    .print-invoice .report-header { text-align: center; margin-bottom: 25px; }
    .print-invoice .org-title { font-family: Georgia, "Times New Roman", Times, serif; font-size: 22pt; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; margin: 0 0 6px; }
    .print-invoice .doc-title { font-family: Georgia, "Times New Roman", Times, serif; font-size: 12pt; font-weight: 400; font-style: italic; color: #333; letter-spacing: .02em; margin: 0; }
    .print-invoice .meta-bar { display: flex; justify-content: space-between; align-items: center; border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; padding: 10px 5px; margin-bottom: 35px; font-size: 9pt; }
    .print-invoice .meta-item { display: flex; gap: 6px; }
    .print-invoice .meta-label { font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
    .print-invoice .financial-statement { width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums; margin-bottom: 50px; }
    .print-invoice .financial-statement th, .print-invoice .financial-statement td { padding: 8px 5px; vertical-align: bottom; border: none; }
    .print-invoice .financial-statement th { text-align: right; font-size: 8pt; font-weight: 600; text-transform: uppercase; color: #333; border-bottom: 1px solid #000; padding-bottom: 10px; }
    .print-invoice .financial-statement th.col-desc { text-align: left; }
    .print-invoice .col-amt { text-align: right; width: 180px; }
    .print-invoice .section-header td { font-family: Georgia, "Times New Roman", Times, serif; font-size: 10pt; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; padding-top: 25px; padding-bottom: 10px; background: none; color: #000; }
    .print-invoice .line-item td { font-size: 10pt; border-bottom: 1px dotted #ccc; }
    .print-invoice .line-item .col-desc { padding-left: 15px; }
    .print-invoice .subtotal-row td { font-weight: 600; padding-top: 12px; padding-bottom: 12px; border-bottom: none; background: none; }
    .print-invoice .subtotal-row .col-amt { border-top: 1px solid #000; }
    .print-invoice .grand-total-row td { font-family: Georgia, "Times New Roman", Times, serif; font-weight: 700; font-size: 11pt; padding-top: 15px; padding-bottom: 15px; text-transform: uppercase; background: none; color: #000; }
    .print-invoice .grand-total-row .col-amt { border-top: 2px solid #000; border-bottom: 4px double #000; }
    .print-invoice .signatures-area { padding-top: 60px; display: flex; justify-content: space-between; page-break-inside: avoid; }
    .print-invoice .sig-box { width: 25%; text-align: center; }
    .print-invoice .sig-line { border-bottom: 1px solid #000; height: 40px; margin-bottom: 5px; }
    .print-invoice .sig-title { font-size: 8.5pt; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
    .print-invoice .document-footer { margin-top: 40px; display: flex; justify-content: space-between; font-size: 8pt; color: #333; border-top: 1px solid #ccc; padding-top: 15px; page-break-inside: avoid; }

    .print-header {
        display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;
        border-bottom: 2px solid #111827; padding-bottom: 14px; margin-bottom: 18px;
    }
    .print-brand h1 { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: .08em; }
    .print-meta { text-align: right; font-size: 12px; color: #334155; }
    .print-meta strong { color: #111827; }
    .print-bill-title { text-align: center; margin: 10px 0 18px; }
    .print-bill-title h2 { margin: 0; font-size: 22px; letter-spacing: .06em; font-weight: 800; }
    .print-bill-title .sub { margin-top: 5px; font-size: 12px; color: #475569; }
    .print-summary { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    .print-summary td { border: 1px solid #cbd5e1; padding: 10px 12px; font-size: 12px; }
    .print-summary td.label { background: #f8fafc; font-weight: 700; width: 32%; }
    .print-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .print-table th, .print-table td { border: 1px solid #cbd5e1; padding: 11px 14px; font-size: 13px; }
    .print-table td:last-child, .print-table th:last-child { text-align: right; }
    .print-section td {
        background: #111827 !important; color: #fff !important; font-weight: 800;
        letter-spacing: .10em; text-transform: uppercase; font-size: 12px;
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }
    .print-sub td { background: #eef2f7 !important; font-weight: 800; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .print-table .grand-row td {
        background: #000 !important; color: #fff !important; font-size: 16px; font-weight: 900;
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }
    .print-footer { margin-top: 200px; display: grid; grid-template-columns: 1fr 1fr; gap: 60px; }
    .print-sign-box { padding-top: 16px; border-top: 1px solid #111827; text-align: center; font-size: 12px; color: #334155; }
    @media (max-width: 991.98px) { .amb-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 575.98px) {
        .amb-hero, .amb-filter-card, .amb-invoice-body { padding: 18px; }
        .amb-kpi-grid { grid-template-columns: 1fr; }
        .amb-title { font-size: 24px; }
        .amb-table th, .amb-table td { padding: 12px; font-size: 13px; }
    }
    @page { size: A4 portrait; margin: 12mm; }
    @media print {
        body { background: #fff !important; }
        .no-print, .sidebar, .topbar, .page-hero,
        .amb-shell > .amb-hero, .amb-shell > .amb-filter-card,
        .amb-shell > .amb-kpi-grid, .amb-shell > .amb-invoice-card { display: none !important; }
        .content-wrap, .page-body, .page-container, .amb-shell {
            margin: 0 !important; padding: 0 !important; max-width: 100% !important; width: 100% !important;
        }
        .print-only { display: block !important; }
        .print-invoice { display: block !important; }
    }
    @media print {
        @page { size: A4; margin: 10mm; }
        html, body {
            margin: 0 !important; padding: 0 !important; background: #ffffff !important;
            -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important;
        }
        body * { visibility: hidden !important; }
        .print-only, .print-only * { visibility: visible !important; }
        .print-only {
            display: block !important; position: static !important; left: 0 !important; top: 0 !important;
            width: 100% !important; margin: 0 !important; padding: 0 !important;
            background: #ffffff !important; z-index: 999999 !important;
        }
        .app-sidebar, .sidebar, .topbar, .app-topbar, .navbar, header, nav, aside, form, button,
        .btn, .alert, .breadcrumb, .dropdown-menu, .modal, .offcanvas, .no-print {
            display: none !important; visibility: hidden !important;
        }
        .print-invoice {
            display: block !important; width: 100% !important; max-width: 100% !important;
            margin: 0 !important; padding: 0 !important; color: #111827 !important;
            background: #ffffff !important; box-shadow: none !important;
        }
        .print-invoice header, .print-invoice footer,
        .print-invoice .report-header, .print-invoice .document-footer {
            display: block !important; visibility: visible !important;
        }
        .print-invoice .report-header * , .print-invoice .document-footer * {
            visibility: visible !important;
        }
        .print-invoice .document-footer { display: flex !important; }
    }
    @media print {
        .print-only { margin-top: 0 !important; padding-top: 0 !important; }
        .print-invoice { margin-top: 0 !important; padding-top: 0 !important; }
        .print-invoice .report-header { margin-top: 0 !important; padding-top: 0 !important; }
        .amb-shell, .content-wrap, .page-body, .page-container { display: block !important; }
        .print-invoice .line-item td.col-desc,
        .print-invoice .line-item td.col-amt { border-bottom: 1px dotted #ccc !important; }
        .print-invoice .subtotal-row td.col-desc,
        .print-invoice .subtotal-row td.col-amt { border-top: 1px solid #000 !important; border-bottom: none !important; }
        .print-invoice .grand-total-row td.col-desc,
        .print-invoice .grand-total-row td.col-amt { border-top: 2px solid #000 !important; border-bottom: 4px double #000 !important; }
        .print-invoice .signatures-area { display: flex !important; justify-content: space-between !important; gap: 24px !important; }
        .print-invoice .sig-box { flex: 1 !important; width: auto !important; }
    }
</style>

<div class="amb-shell">
    <div class="amb-hero mb-4">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h1 class="amb-title">Admin Mess Bill</h1>
                <div class="amb-subtitle">
                    Cycle {{ $monthCycle }} &middot; {{ $rangeStart->format('d M Y') }} to {{ $rangeEnd->format('d M Y') }}
                </div>
            </div>
            <div class="amb-cycle-pill">{{ $monthCycle }}</div>
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
                <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" target="_blank" class="btn btn-outline-dark btn-lg">Print Bill</a>
            </div>
        </form>
    </div>

    <div class="amb-kpi-grid mb-4 no-print">
        <div class="amb-kpi-card">
            <div class="amb-kpi-label">Balance Amount</div>
            <div class="amb-kpi-value">{{ number_format($balanceAmount, 2) }}</div>
            <div class="amb-kpi-note">Total expenses minus guest</div>
        </div>
        <div class="amb-kpi-card">
            <div class="amb-kpi-label">Worker/Employees</div>
            <div class="amb-kpi-value">{{ number_format($workerAmount, 2) }}</div>
            <div class="amb-kpi-note">{{ number_format($workerDays) }} days &times; {{ number_format($contractorRate, 2) }}</div>
        </div>
        <div class="amb-kpi-card">
            <div class="amb-kpi-label">Net Balance</div>
            <div class="amb-kpi-value">{{ number_format($netBalance, 2) }}</div>
            <div class="amb-kpi-note">Balance minus worker share</div>
        </div>
        <div class="amb-kpi-card">
            <div class="amb-kpi-label">Total Amount</div>
            <div class="amb-kpi-value">{{ number_format($totalAmount, 2) }}</div>
            <div class="amb-kpi-note">50% + guest + worker</div>
        </div>
    </div>

    <div class="amb-invoice-card">
        <div class="amb-invoice-header">
            <div>
                <h2 class="amb-invoice-title">ADMIN MESS BILL</h2>
                <div class="mt-1 opacity-75">{{ $rangeStart->format('d M Y') }} to {{ $rangeEnd->format('d M Y') }}</div>
            </div>
            <div class="amb-cycle-pill">Cycle {{ $monthCycle }}</div>
        </div>

        <div class="amb-invoice-body">
            <div class="table-responsive">
                <table class="amb-table align-middle">
                    <tbody>
                        <tr class="amb-section"><th colspan="2">Working</th></tr>
                        <tr>
                            <th>Total Expenses</th>
                            <td>{{ number_format($totalExpenses, 2) }}</td>
                        </tr>
                        <tr class="amb-less">
                            <th>Less: Guest Amount</th>
                            <td>({{ number_format($guestAmount, 2) }})</td>
                        </tr>
                        <tr class="amb-sub">
                            <th>Balance Amount</th>
                            <td>{{ number_format($balanceAmount, 2) }}</td>
                        </tr>
                        <tr class="amb-less">
                            <th>Less: Worker/Employees ({{ number_format($workerDays) }} days &times; {{ number_format($contractorRate, 2) }})</th>
                            <td>({{ number_format($workerAmount, 2) }})</td>
                        </tr>
                        <tr class="amb-sub">
                            <th>Net Balance</th>
                            <td>{{ number_format($netBalance, 2) }}</td>
                        </tr>
                        <tr class="amb-section"><th colspan="2">Payable</th></tr>
                        <tr>
                            <th>Company Share (50% of Net Balance)</th>
                            <td>{{ number_format($companyPaid, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Add: Guest Amount</th>
                            <td>{{ number_format($guestAmount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Add: Worker/Employees Amount</th>
                            <td>{{ number_format($workerAmount, 2) }}</td>
                        </tr>
                        <tr class="amb-row-total">
                            <th>Total Amount to be Paid</th>
                            <td>{{ number_format($totalAmount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="print-only print-invoice">
        <header class="report-header">
            <h1 class="org-title">Admin Mess</h1>
            <h2 class="doc-title">Bill Statement</h2>
        </header>

        <div class="meta-bar">
            <div class="meta-item"><span class="meta-label">Month Cycle:</span><span class="meta-value">{{ $monthCycle }}</span></div>
            <div class="meta-item"><span class="meta-label">Billing Period:</span><span class="meta-value">{{ $rangeStart->format('d M Y') }} &ndash; {{ $rangeEnd->format('d M Y') }}</span></div>
            <div class="meta-item"><span class="meta-label">Printed on:</span><span class="meta-value">{{ now()->format('d M Y h:i A') }}</span></div>
        </div>

        <table class="financial-statement">
            <thead>
                <tr><th class="col-desc">Description</th><th class="col-amt">Amount (PKR)</th></tr>
            </thead>
            <tbody>
                <tr class="section-header"><td colspan="2">Working</td></tr>
                <tr class="line-item"><td class="col-desc">Total Expenses</td><td class="col-amt">{{ number_format($totalExpenses, 2) }}</td></tr>
                <tr class="line-item"><td class="col-desc">Less: Guest Amount</td><td class="col-amt">({{ number_format($guestAmount, 2) }})</td></tr>
                <tr class="subtotal-row"><td class="col-desc">Balance Amount</td><td class="col-amt">{{ number_format($balanceAmount, 2) }}</td></tr>
                <tr class="line-item"><td class="col-desc">Less: Worker/Employees ({{ number_format($workerDays) }} days &times; {{ number_format($contractorRate, 2) }})</td><td class="col-amt">({{ number_format($workerAmount, 2) }})</td></tr>
                <tr class="subtotal-row"><td class="col-desc">Net Balance</td><td class="col-amt">{{ number_format($netBalance, 2) }}</td></tr>

                <tr class="section-header"><td colspan="2">Payable</td></tr>
                <tr class="line-item"><td class="col-desc">Company Share (50% of Net Balance)</td><td class="col-amt">{{ number_format($companyPaid, 2) }}</td></tr>
                <tr class="line-item"><td class="col-desc">Add: Guest Amount</td><td class="col-amt">{{ number_format($guestAmount, 2) }}</td></tr>
                <tr class="line-item"><td class="col-desc">Add: Worker/Employees Amount</td><td class="col-amt">{{ number_format($workerAmount, 2) }}</td></tr>

                <tr class="grand-total-row"><td class="col-desc">TOTAL AMOUNT TO BE PAID</td><td class="col-amt">{{ number_format($totalAmount, 2) }}</td></tr>
            </tbody>
        </table>

        <div class="signatures-area">
            <div class="sig-box"><div class="sig-line"></div><div class="sig-title">Prepared by</div></div>
            <div class="sig-box"><div class="sig-line"></div><div class="sig-title">Checked by</div></div>
            <div class="sig-box"><div class="sig-line"></div><div class="sig-title">Approved by</div></div>
        </div>

        <footer class="document-footer">
            <div>Admin Mess Bill Statement &mdash; Cycle {{ $monthCycle }}</div>
            <div>Page 1 of 1</div>
        </footer>
    </div>
</div>
@endsection
