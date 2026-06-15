@extends('layouts.app')

@section('content')
{{-- PAYMENT_DATE_STATEMENT_20260615 --}}
<div class="container-fluid py-2 compact-statement-page">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.statement.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Member</label>
                    <select name="member_id" class="form-select">
                        @foreach($members as $m)
                            <option value="{{ $m->id }}" @selected((int) $memberId === (int) $m->id)>
                                {{ $m->member_code }} - {{ $m->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Single Month</label>
                    <input type="month" name="single_month" value="{{ $singleMonth }}" class="form-control">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">From Month</label>
                    <input type="month" name="from_month" value="{{ $fromMonth }}" class="form-control">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">To Month</label>
                    <input type="month" name="to_month" value="{{ $toMonth }}" class="form-control">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-secondary flex-fill" type="submit">View</button>
                    <button class="btn btn-success flex-fill" type="submit" name="export" value="csv">Excel</button>
                </div>

                <div class="col-md-2">
                    <button type="button" onclick="printStatementOnly()" class="btn btn-primary w-100">Print Statement</button>
                </div>
            </form>
        </div>
    </div>

    <div class="statement-print mx-auto bg-white border rounded shadow-sm p-2" style="max-width: 1080px;">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h3 class="mb-1 fw-bold">Mess Statment</h3>
                <div class="text-muted">Member Account Statement</div>
            </div>
            <div class="text-muted small">Generated: {{ now()->format('Y-m-d') }}</div>
        </div>

        <hr>

        <div class="row small fw-semibold mb-2">
            <div class="col-md-2">Member ID:</div>
            <div class="col-md-4 fw-normal">{{ $member->member_code ?? '-' }}</div>
            <div class="col-md-2">Name:</div>
            <div class="col-md-4 fw-normal">{{ $member->name ?? '-' }}</div>
        </div>

        <div class="row small fw-semibold mb-2">
            <div class="col-md-2">Department:</div>
            <div class="col-md-4 fw-normal">{{ $member->department_name ?? '-' }}</div>
            <div class="col-md-2">Mess:</div>
            <div class="col-md-4 fw-normal">{{ $messName }}</div>
        </div>

        <div class="row small fw-semibold mb-2">
            <div class="col-md-2">Join Date:</div>
            <div class="col-md-4 fw-normal">{{ $member->join_date ?? '-' }}</div>
            <div class="col-md-2">Leave Date:</div>
            <div class="col-md-4 fw-normal">{{ $member->leave_date ?? '-' }}</div>
        </div>

        <div class="row small fw-semibold mb-3">
            <div class="col-md-2">Statement Month:</div>
            <div class="col-md-10 fw-normal">{{ $fromMonth }} to {{ $toMonth }}</div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <div class="border rounded p-2">
                    <div class="text-muted">Opening Balance</div>
                    <div class="h5 mb-0 fw-bold">{{ number_format($openingBalance, 2) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-2">
                    <div class="text-muted">Total Debit</div>
                    <div class="h5 mb-0 fw-bold">{{ number_format($totalDebit, 2) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-2">
                    <div class="text-muted">Total Credit</div>
                    <div class="h5 mb-0 fw-bold">{{ number_format($totalCredit, 2) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-2">
                    <div class="text-muted">Closing Balance</div>
                    <div class="h5 mb-0 fw-bold">{{ number_format($closingBalance, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle statement-table-compact">
                <thead class="table-light">
                    <tr>
                        <th>Month</th>
                        <th>Pay Date</th>
                        <th>Days</th>
                        <th>Rate/Day</th>
                        <th>Amount</th>
                        <th>Ref Type</th>
                        <th>Ref ID</th>
                        <th>Debit</th>
                        <th>Credit</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->month }}</td>
                            <td>{{ $row->payment_date ?? '' }}</td>
                            <td>{{ $row->days }}</td>
                            <td>{{ $row->rate_per_day !== '' ? number_format((float) $row->rate_per_day, 2) : '' }}</td>
                            <td>{{ number_format((float) $row->total_amount, 2) }}</td>
                            <td>{{ $row->ref_type }}</td>
                            <td>{{ $row->ref_id }}</td>
                            <td>{{ number_format((float) $row->debit, 2) }}</td>
                            <td>{{ number_format((float) $row->credit, 2) }}</td>
                            <td>{{ number_format((float) $row->running_balance, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No statement rows found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="text-center text-muted small mt-3">
            This is a system-generated statement and does not require any signature or stamp.
        </div>
    </div>
</div>



<style>
/* STATEMENT_SCREEN_AND_PRINT_CLEAN_20260615 */

/* Screen compact layout */
.compact-statement-page .card-body {
    padding: 0.65rem 0.85rem;
}

.statement-print {
    font-size: 12px;
    line-height: 1.25;
}

.statement-print h3 {
    font-size: 1.25rem;
    margin-bottom: 0.15rem !important;
}

.statement-print hr {
    margin: 0.45rem 0;
}

.statement-print .mb-3 {
    margin-bottom: 0.55rem !important;
}

.statement-print .mb-2 {
    margin-bottom: 0.35rem !important;
}

.statement-print .p-2 {
    padding: 0.35rem !important;
}

.statement-print .h5 {
    font-size: 0.95rem;
}

.statement-table-compact {
    font-size: 11px;
    margin-bottom: 0;
    width: 100%;
}

.statement-table-compact th,
.statement-table-compact td {
    padding: 0.18rem 0.28rem !important;
    white-space: nowrap;
    vertical-align: middle;
}

.statement-table-compact th {
    font-size: 10.5px;
    font-weight: 700;
}

/* Print: same as screen, A4 portrait, no duplicate/conflicting rules */
@media print {
    @page {
        size: A4 portrait;
        margin: 5mm;
    }

    html,
    body {
        background: #ffffff !important;
        width: 210mm !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }

    body {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    nav,
    header,
    footer,
    aside,
    form,
    .sidebar,
    .navbar,
    .topbar,
    .btn,
    .compact-statement-page > .card {
        display: none !important;
    }

    main,
    .content,
    .content-wrapper,
    .main-content,
    .page-content,
    .app-content,
    .container,
    .container-fluid,
    .compact-statement-page {
        width: 200mm !important;
        max-width: 200mm !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        overflow: visible !important;
    }

    .statement-print {
        display: block !important;
        position: static !important;
        width: 200mm !important;
        max-width: 200mm !important;
        margin: 0 auto !important;
        padding: 3mm !important;
        box-sizing: border-box !important;
        border: 1px solid #d8dee8 !important;
        border-radius: 3px !important;
        box-shadow: none !important;
        background: #ffffff !important;
        color: #111827 !important;
        font-size: 7.7px !important;
        line-height: 1.18 !important;
    }

    .statement-print h3 {
        font-size: 13px !important;
        line-height: 1.15 !important;
        margin: 0 0 4px 0 !important;
        font-weight: 700 !important;
    }

    .statement-print .text-muted {
        color: #53657f !important;
    }

    .statement-print hr {
        margin: 5px 0 !important;
        border-color: #b9c3d0 !important;
    }

    .statement-print .row {
        display: flex !important;
        flex-wrap: wrap !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    .statement-print .col-md-2 {
        flex: 0 0 16.666666% !important;
        max-width: 16.666666% !important;
        width: 16.666666% !important;
    }

    .statement-print .col-md-3 {
        flex: 0 0 25% !important;
        max-width: 25% !important;
        width: 25% !important;
    }

    .statement-print .col-md-4 {
        flex: 0 0 33.333333% !important;
        max-width: 33.333333% !important;
        width: 33.333333% !important;
    }

    .statement-print .col-md-10 {
        flex: 0 0 83.333333% !important;
        max-width: 83.333333% !important;
        width: 83.333333% !important;
    }

    .statement-print .mb-3 {
        margin-bottom: 5px !important;
    }

    .statement-print .mb-2 {
        margin-bottom: 3px !important;
    }

    .statement-print .g-2 {
        --bs-gutter-x: 4px !important;
        --bs-gutter-y: 4px !important;
    }

    .statement-print .border.rounded.p-2 {
        padding: 3px 5px !important;
        border: 1px solid #d8dee8 !important;
        border-radius: 3px !important;
    }

    .statement-print .h5 {
        font-size: 11px !important;
        font-weight: 700 !important;
        margin: 0 !important;
    }

    .statement-print .table-responsive {
        width: 100% !important;
        overflow: visible !important;
    }

    .statement-table-compact {
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
        font-size: 7.3px !important;
        margin: 0 !important;
    }

    .statement-table-compact th,
    .statement-table-compact td {
        padding: 2.1px 2.3px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: clip !important;
        vertical-align: middle !important;
        border: 1px solid #cfd6df !important;
    }

    .statement-table-compact th {
        font-size: 7.1px !important;
        font-weight: 700 !important;
        color: #53657f !important;
        background: #f3f6f9 !important;
        letter-spacing: 0.15px !important;
    }

    .statement-table-compact th:nth-child(1),
    .statement-table-compact td:nth-child(1) { width: 8.5% !important; }

    .statement-table-compact th:nth-child(2),
    .statement-table-compact td:nth-child(2) { width: 12% !important; }

    .statement-table-compact th:nth-child(3),
    .statement-table-compact td:nth-child(3) { width: 5.5% !important; }

    .statement-table-compact th:nth-child(4),
    .statement-table-compact td:nth-child(4) { width: 9.5% !important; }

    .statement-table-compact th:nth-child(5),
    .statement-table-compact td:nth-child(5) { width: 11.5% !important; }

    .statement-table-compact th:nth-child(6),
    .statement-table-compact td:nth-child(6) { width: 15% !important; }

    .statement-table-compact th:nth-child(7),
    .statement-table-compact td:nth-child(7) { width: 7% !important; }

    .statement-table-compact th:nth-child(8),
    .statement-table-compact td:nth-child(8) { width: 9% !important; }

    .statement-table-compact th:nth-child(9),
    .statement-table-compact td:nth-child(9) { width: 10% !important; }

    .statement-table-compact th:nth-child(10),
    .statement-table-compact td:nth-child(10) { width: 12% !important; }

    .text-center.text-muted.small.mt-3 {
        margin-top: 6px !important;
        font-size: 6.5px !important;
    }
}
</style>


<script>
/* SAFE_STATEMENT_PRINT_WINDOW_20260615 */
function printStatementOnly() {
    const statement = document.querySelector('.statement-print');
    if (!statement) {
        window.print();
        return;
    }

    const printWindow = window.open('', '_blank', 'width=900,height=1200');
    if (!printWindow) {
        window.print();
        return;
    }

    const html = `
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Mess Statment</title>
<style>
@page {
    size: A4 portrait;
    margin: 6mm;
}
* {
    box-sizing: border-box;
}
html, body {
    margin: 0;
    padding: 0;
    background: #fff;
    color: #111827;
    font-family: Arial, Helvetica, sans-serif;
}
body {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.statement-print {
    width: 198mm;
    max-width: 198mm;
    margin: 0 auto;
    padding: 3mm;
    border: 1px solid #d8dee8;
    border-radius: 4px;
    background: #fff;
    font-size: 7.8px;
    line-height: 1.18;
}
.d-flex { display: flex; }
.justify-content-between { justify-content: space-between; }
.align-items-start { align-items: flex-start; }
h3 {
    font-size: 13px;
    margin: 0 0 4px 0;
    font-weight: 700;
}
.text-muted { color: #53657f; }
.small { font-size: 7px; }
hr {
    border: 0;
    border-top: 1px solid #b9c3d0;
    margin: 5px 0;
}
.row {
    display: flex;
    flex-wrap: wrap;
    width: 100%;
    margin: 0 0 3px 0;
}
.col-md-2 { width: 16.666666%; }
.col-md-3 { width: 25%; padding-right: 3px; }
.col-md-4 { width: 33.333333%; }
.col-md-10 { width: 83.333333%; }
.fw-semibold { font-weight: 600; }
.fw-normal { font-weight: 400; }
.fw-bold { font-weight: 700; }
.mb-1, .mb-2, .mb-3 { margin-bottom: 3px; }
.border.rounded.p-2 {
    border: 1px solid #d8dee8;
    border-radius: 4px;
    padding: 3px 5px;
}
.h5 {
    font-size: 11px;
    margin: 0;
    font-weight: 700;
}
.table-responsive {
    width: 100%;
    overflow: visible;
}
table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 7.3px;
}
th, td {
    border: 1px solid #cfd6df;
    padding: 2.1px 2.3px;
    white-space: nowrap;
    overflow: hidden;
    vertical-align: middle;
}
th {
    background: #f3f6f9;
    color: #53657f;
    font-size: 7.1px;
    font-weight: 700;
}
th:nth-child(1), td:nth-child(1) { width: 8.5%; }
th:nth-child(2), td:nth-child(2) { width: 12%; }
th:nth-child(3), td:nth-child(3) { width: 5.5%; }
th:nth-child(4), td:nth-child(4) { width: 9.5%; }
th:nth-child(5), td:nth-child(5) { width: 11.5%; }
th:nth-child(6), td:nth-child(6) { width: 15%; }
th:nth-child(7), td:nth-child(7) { width: 7%; }
th:nth-child(8), td:nth-child(8) { width: 9%; }
th:nth-child(9), td:nth-child(9) { width: 10%; }
th:nth-child(10), td:nth-child(10) { width: 12%; }
.text-center { text-align: center; }
.mt-3 { margin-top: 6px; }
</style>
</head>
<body>
${statement.outerHTML}
<script>
window.onload = function () {
    setTimeout(function () {
        window.print();
        window.close();
    }, 250);
};
<\/script>
</body>
</html>
    `;

    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
}
</script>

@endsection
