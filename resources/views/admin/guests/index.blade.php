@extends('layouts.app')

@section('title', 'Guest Management')
@section('page_title', 'Guest Management')

@section('content')
<div class="container-fluid px-0 guest-management-page">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

{{-- Guest management dashboard --}}
@php
    $gmFrom = request('from_date') ?: request('gm_from');
    $gmTo = request('to_date') ?: request('gm_to');
    $thisMonthStart = now()->startOfMonth()->toDateString();
    $thisMonthEnd = now()->endOfMonth()->toDateString();
    $guestsThisMonth = $guests->filter(function ($guest) use ($thisMonthStart, $thisMonthEnd) {
        $guestDate = optional($guest->date)->format('Y-m-d');
        return $guestDate && $guestDate >= $thisMonthStart && $guestDate <= $thisMonthEnd;
    })->count();
    $pendingApprovalCount = $guestMealReportRows->filter(fn ($row) => empty($row->approved_at))->count();
@endphp

<div class="guest-hero card guest-panel mb-4 no-print">
    <div class="card-body p-3 p-xl-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="text-uppercase text-muted small fw-bold mb-1">Mess administration</div>
                <h1 class="h3 mb-1">Guest management</h1>
                <p class="text-muted mb-0">Create guest meal drafts, review meal reports, and manage imports from one clean workspace.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-outline-primary guest-btn-outline" type="button" data-bs-toggle="collapse" data-bs-target="#guestImportPanel" aria-expanded="false" aria-controls="guestImportPanel">Import</button>
                <a href="#quickAddGuestMeal" class="btn btn-primary guest-btn-primary">Add guest meal</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4 no-print">
    <div class="col-sm-6 col-xl-3">
        <div class="guest-metric card h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold">Guests this month</div>
                <div class="fs-3 fw-bold">{{ number_format($guestsThisMonth) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="guest-metric card h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold">Meals served</div>
                <div class="fs-3 fw-bold">{{ number_format((float) $guestMealReportQtyTotal, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="guest-metric card h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold">Pending approval</div>
                <div class="fs-3 fw-bold">{{ number_format($pendingApprovalCount) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="guest-metric card h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold">Total amount</div>
                <div class="fs-3 fw-bold">{{ number_format((float) $guestMealReportGrandTotal, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="collapse no-print" id="guestImportPanel">
    <div class="card guest-panel mb-4">
        <div class="card-header guest-panel-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <span>Guest setup and imports</span>
            <span class="text-muted small">Today guest rate: {{ $currentRate !== null ? number_format($currentRate, 2) : 'Not configured' }}</span>
        </div>
        <div class="card-body p-3 p-xl-4">
            <div class="row g-3 g-xl-4">
                <div class="col-xl-6 col-12">
                    <div class="guest-secondary-box h-100">
                        <h2 class="h6 mb-3">Create guest</h2>
                        <form method="POST" action="{{ route('admin.guests.store') }}" class="row g-3">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label">Guest code</label>
                                <input type="text" name="guest_code" class="form-control guest-control" placeholder="Auto G00001">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date</label>
                                <input type="date" name="date" class="form-control guest-control" value="{{ $today }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Host member</label>
                                <select name="host_member_id" class="form-select guest-control">
                                    <option value="">None</option>
                                    @foreach($members as $member)
                                        <option value="{{ $member->id }}">{{ $member->member_code }} - {{ $member->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6 col-12">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control guest-control" required>
                            </div>
                            <div class="col-lg-6 col-12">
                                <label class="form-label">Company / Came from</label>
                                <input type="text" name="came_from" class="form-control guest-control">
                            </div>
                            <div class="col-lg-6 col-12">
                                <label class="form-label">Department</label>
                                <select name="department_id" class="form-select guest-control" required>
                                    <option value="">Select department</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}">{{ is_object($department) ? ($department->code ?? '') : $department }} - {{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6 col-12">
                                <label class="form-label">Remarks</label>
                                <input type="text" name="remarks" class="form-control guest-control">
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary guest-btn-primary">Save guest</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-xl-6 col-12">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="guest-secondary-box">
                                <h2 class="h6 mb-3">Bulk import guests</h2>
                                <form method="POST" action="{{ route('admin.guests.import') }}" enctype="multipart/form-data" class="row g-3">
                                    @csrf
                                    <div class="col-12"><input type="file" name="file" class="form-control guest-control" accept=".csv,.txt" required></div>
                                    <div class="col-12"><button class="btn btn-outline-primary guest-btn-outline">Import guests CSV</button></div>
                                    <div class="col-12 text-muted small">Headers: guest_code,date,name,came_from/company,remarks,department_id/department_code/department,host_member_id/host_member_code,is_active,is_deleted</div>
                                </form>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="guest-secondary-box">
                                <h2 class="h6 mb-3">Bulk import guest meals</h2>
                                <form method="POST" action="{{ route('admin.guests.meals.import') }}" enctype="multipart/form-data" class="row g-3">
                                    @csrf
                                    <div class="col-12"><input type="file" name="file" class="form-control guest-control" accept=".csv,.txt" required></div>
                                    <div class="col-12 d-flex gap-2 flex-wrap">
                                        <button class="btn btn-outline-primary guest-btn-outline">Import meals CSV</button>
                                        <a href="{{ route('admin.guests.meals.export', ['from' => $fromDate, 'to' => $toDate]) }}" class="btn btn-outline-secondary guest-btn-outline">Export meals</a>
                                    </div>
                                    <div class="col-12 text-muted small">Headers: guest_id/guest_code,date/meal_date,meal_type,qty/quantity</div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card guest-panel mb-4 no-print" id="quickAddGuestMeal">
    <div class="card-header guest-panel-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
        <span>Quick add meal</span>
        <span class="small text-muted">Create guest meal draft</span>
    </div>
    <div class="card-body p-3 p-xl-4">
        <form method="POST" action="{{ route('admin.guests.meals.store') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-lg-4 col-md-6 col-12">
                <label class="form-label">Guest</label>
                <select name="guest_id" class="form-select guest-control js-guest-meal-search" required>
                    <option value=""></option>
                    @foreach($guests as $guest)
                        <option value="{{ $guest->id }}">{{ $guest->name ?? "Guest" }} · {{ $guest->came_from ?? "-" }} · {{ $guest->guest_code ?? "" }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label">Date</label>
                <input type="date" name="meal_date" class="form-control guest-control" value="{{ $today }}" required>
            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <label class="form-label">Meals</label>
                <div class="d-flex flex-wrap gap-3 align-items-center guest-meal-type-checks">
                    @foreach($mealTypes as $mealType)
                        <label class="form-check d-flex align-items-center gap-2 mb-0">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="meal_types[]"
                                value="{{ $mealType }}"
                                @checked(is_array(old('meal_types')) && in_array($mealType, old('meal_types'), true))
                            >
                            <span class="form-check-label">{{ $mealType }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-1 col-md-3 col-6">
                <label class="form-label">Qty</label>
                <input type="number" min="1" name="quantity" class="form-control guest-control" value="{{ old('quantity', 1) }}" required>
            </div>
            <div class="col-lg-2 col-md-12 col-12 d-grid">
                <button class="btn btn-primary guest-btn-primary">Add</button>
            </div>
        </form>
    </div>
</div>

{{-- Guest Meal Printable Report --}}
<div class="card guest-panel mb-4 guest-meal-report-print-area">
    <div class="card-header guest-panel-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <strong>Guest meal report</strong>
            <div class="small text-muted">
                {{ $gmFrom ?: 'All dates' }} to {{ $gmTo ?: 'All dates' }}
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-end no-print">
            <div class="btn-group btn-group-sm guest-filter-chips" role="group" aria-label="Guest meal report filters">
                <a href="{{ route('admin.guests.index', ['from_date' => $thisMonthStart, 'to_date' => $thisMonthEnd]) }}" class="btn btn-outline-secondary">This month</a>
                <span class="btn btn-outline-secondary disabled" title="Pending needs no existing GET filter, so backend was not changed.">Pending</span>
                <a href="{{ route('admin.guests.index') }}" class="btn btn-outline-secondary">All</a>
            </div>
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label small mb-1">From date</label>
                    <input type="date" name="from_date" value="{{ $gmFrom }}" class="form-control form-control-sm guest-control">
                </div>
                <div>
                    <label class="form-label small mb-1">To date</label>
                    <input type="date" name="to_date" value="{{ $gmTo }}" class="form-control form-control-sm guest-control">
                </div>
                <button type="submit" class="btn btn-primary btn-sm guest-btn-primary">Apply</button>
                <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm guest-btn-outline">Print</button>
            </form>

            <form method="POST" action="{{ route('admin.guests.meals.approve-range') }}" class="d-flex align-items-end gap-2">
                @csrf
                <input type="hidden" name="from_date" value="{{ $gmFrom }}">
                <input type="hidden" name="to_date" value="{{ $gmTo }}">
                <button type="submit" class="btn btn-success btn-sm guest-btn-success" onclick="return confirm('Approve all unapproved guest meals in selected date range?')">Approve selected</button>
                <a href="{{ route('admin.guests.print', ['from_date' => $gmFrom, 'to_date' => $gmTo]) }}" target="_blank" class="btn btn-primary btn-sm guest-btn-success">Print report</a>
            </form>
        </div>
    </div>

    <div class="card-body p-3 p-xl-4">
        <div class="print-title d-none">
            <h3 class="mb-1">Guest meal report</h3>
            <p class="mb-2">
                Complete date range:
                <strong>{{ $gmFrom ? \Illuminate\Support\Carbon::parse($gmFrom)->format('d-M-Y') : 'All dates' }}</strong>
                to
                <strong>{{ $gmTo ? \Illuminate\Support\Carbon::parse($gmTo)->format('d-M-Y') : 'All dates' }}</strong>
            </p>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Meal date</th>
                        <th>Guest name</th>
                        <th>Company/Came from</th>
                        <th>Meal type</th>
                        <th>Status</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Rate</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($guestMealReportRows as $row)
                        <tr>
                            <td>{{ optional($row->meal_date)->format('d-M-Y') }}</td>
                            <td>
                                {{ $row->guest?->name ?: '-' }}
                                @if($row->guest?->guest_code)
                                    <span class="text-muted small">({{ $row->guest->guest_code }})</span>
                                @endif
                            </td>
                            <td>{{ $row->guest?->came_from ?: '-' }}</td>
                            <td>{{ $row->meal_type ?: '-' }}</td>
                            <td>
                                @if($row->rate_missing)
                                    <span class="badge bg-danger">Rate missing</span>
                                @else
                                    <span class="badge {{ $row->approved_at ? 'bg-success' : 'bg-warning text-dark' }}">{{ $row->approval_status }}</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format((float) $row->quantity, 2) }}</td>
                            <td class="text-end">{{ $row->rate_missing ? 'Missing' : number_format((float) $row->rate_display, 2) }}</td>
                            <td class="text-end">{{ $row->rate_missing ? '-' : number_format((float) $row->amount_display, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No guest meal records found.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="fw-bold table-light">
                        <td colspan="5" class="text-end">Grand total</td>
                        <td class="text-end">{{ number_format((float) $guestMealReportQtyTotal, 2) }}</td>
                        <td></td>
                        <td class="text-end">{{ number_format((float) $guestMealReportGrandTotal, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>


<style>
.guest-management-page {
    padding: 0 1rem 1.5rem;
}

.guest-management-page .alert {
    border: 0;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
}

.guest-panel {
    border: 1px solid rgba(148, 163, 184, .22);
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 12px 30px rgba(15, 23, 42, .06);
    overflow: hidden;
}

.guest-panel-header {
    min-height: 48px;
    padding: .95rem 1.15rem;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border-bottom: 1px solid rgba(148, 163, 184, .18);
    font-weight: 800;
    color: #1f2937;
    letter-spacing: -.01em;
}

.guest-management-page .form-label {
    margin-bottom: .35rem;
    color: #374151;
    font-size: .86rem;
    font-weight: 600;
}

.guest-control {
    min-height: 42px;
    border-radius: 12px;
    border-color: #dbe3ef;
    background-color: #fff;
    color: #111827;
}

.guest-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .12);
}

.guest-btn-primary,
.guest-btn-success,
.guest-btn-outline {
    min-height: 42px;
    border-radius: 12px;
    padding-left: 1rem;
    padding-right: 1rem;
    font-weight: 700;
}

.guest-btn-primary {
    box-shadow: 0 8px 18px rgba(13, 110, 253, .18);
}

.guest-management-page .table {
    border-color: #e5e7eb;
}

.guest-management-page .table thead th {
    background: #f8fafc;
    color: #334155;
    font-size: .82rem;
    text-transform: uppercase;
    letter-spacing: .03em;
}

.guest-management-page .badge {
    border-radius: 999px;
    padding: .45rem .65rem;
}


.guest-meal-type-checks {
    min-height: 42px;
    padding: .55rem .75rem;
    border: 1px solid #dbe3ef;
    border-radius: 12px;
    background: #fff;
}

.guest-meal-type-checks .form-check-input {
    margin-top: 0;
}

.guest-meal-type-checks .form-check-label {
    font-weight: 600;
    color: #374151;
}


.guest-hero h1 {
    letter-spacing: -.03em;
}

.guest-metric {
    border: 1px solid rgba(148, 163, 184, .22);
    border-radius: 16px;
    box-shadow: 0 8px 22px rgba(15, 23, 42, .05);
}

.guest-secondary-box {
    border: 1px solid rgba(148, 163, 184, .18);
    border-radius: 16px;
    background: #f8fafc;
    padding: 1rem;
}

.guest-filter-chips .btn {
    min-height: 34px;
    border-radius: 999px !important;
    font-weight: 700;
}

.guest-management-page .card.shadow-sm {
    border: 1px solid rgba(148, 163, 184, .18);
    border-radius: 16px;
    box-shadow: 0 8px 22px rgba(15, 23, 42, .04) !important;
}

@media (max-width: 767.98px) {
    .guest-management-page {
        padding: 0 .25rem 1rem;
    }

    .guest-panel-header {
        padding: .85rem 1rem;
    }
}
</style>

<style>
@media print {
    /* GUEST_MEAL_PRINT_FULL_TABLE_FIX_20260609 */
    @page {
        size: A4 portrait;
        margin: 7mm;
    }

    html,
    body {
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    body * {
        visibility: hidden !important;
    }

    .guest-meal-report-print-area,
    .guest-meal-report-print-area * {
        visibility: visible !important;
    }

    .guest-meal-report-print-area {
        position: static !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        box-shadow: none !important;
        background: #ffffff !important;
    }

    .guest-meal-report-print-area .card-header {
        display: none !important;
    }

    .guest-meal-report-print-area .card-body {
        padding: 0 !important;
    }

    .guest-meal-report-print-area .table-responsive {
        overflow: visible !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    .guest-meal-report-print-area table {
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
        font-size: 8.5px !important;
    }

    .guest-meal-report-print-area th,
    .guest-meal-report-print-area td {
        padding: 5px 6px !important;
        white-space: normal !important;
        word-break: break-word !important;
        vertical-align: middle !important;
    }

    .guest-meal-report-print-area thead {
        display: table-header-group !important;
    }

    .guest-meal-report-print-area tbody {
        display: table-row-group !important;
    }

    .guest-meal-report-print-area tfoot {
        display: table-footer-group !important;
    }

    .guest-meal-report-print-area tfoot tr,
    .guest-meal-report-print-area tfoot td {
        font-weight: 800 !important;
        background: #f3f4f6 !important;
        color: #111827 !important;
    }

    /* GUEST_MEAL_PRINT_HIDE_STATUS_COLUMN_20260609 */
    .guest-meal-report-print-area table thead th:nth-child(5),
    .guest-meal-report-print-area table tbody td:nth-child(5) {
        display: none !important;
        visibility: hidden !important;
    }

    .guest-meal-report-print-area table {
        font-size: 8.5px !important;
    }

    /* GUEST_MEAL_PRINT_PORTRAIT_CLEAN_20260609 */
    /* GUEST_MEAL_PRINT_BLANK_RECOVERY_20260609 */
    .guest-meal-report-print-area {
        display: block !important;
        visibility: visible !important;
    }

    .guest-meal-report-print-area {
        page-break-inside: auto !important;
    }

    .guest-meal-report-print-area tr {
        page-break-inside: avoid !important;
        page-break-after: auto !important;
    }

    .guest-meal-report-print-area th:nth-child(1),
    .guest-meal-report-print-area td:nth-child(1) {
        width: 12% !important;
    }

    .guest-meal-report-print-area th:nth-child(2),
    .guest-meal-report-print-area td:nth-child(2) {
        width: 22% !important;
    }

    .guest-meal-report-print-area th:nth-child(3),
    .guest-meal-report-print-area td:nth-child(3) {
        width: 22% !important;
    }

    .guest-meal-report-print-area th:nth-child(4),
    .guest-meal-report-print-area td:nth-child(4) {
        width: 12% !important;
    }

    .guest-meal-report-print-area th:nth-child(6),
    .guest-meal-report-print-area td:nth-child(6) {
        width: 8% !important;
        white-space: nowrap !important;
    }

    .guest-meal-report-print-area th:nth-child(7),
    .guest-meal-report-print-area td:nth-child(7) {
        width: 9% !important;
        white-space: nowrap !important;
    }

    .guest-meal-report-print-area th:nth-child(8),
    .guest-meal-report-print-area td:nth-child(8) {
        width: 13% !important;
        white-space: nowrap !important;
        word-break: normal !important;
        overflow-wrap: normal !important;
    }

    /* GUEST_MEAL_PRINT_AMOUNT_WRAP_FIX_20260609 */
    .guest-meal-report-print-area table {
        font-size: 8px !important;
    }

    .guest-meal-report-print-area th,
    .guest-meal-report-print-area td {
        padding: 4px 4px !important;
    }

    .guest-meal-report-print-area th:nth-child(1),
    .guest-meal-report-print-area td:nth-child(1) {
        width: 11% !important;
    }

    .guest-meal-report-print-area th:nth-child(2),
    .guest-meal-report-print-area td:nth-child(2) {
        width: 21% !important;
    }

    .guest-meal-report-print-area th:nth-child(3),
    .guest-meal-report-print-area td:nth-child(3) {
        width: 20% !important;
    }

    .guest-meal-report-print-area th:nth-child(4),
    .guest-meal-report-print-area td:nth-child(4) {
        width: 10% !important;
    }

    .guest-meal-report-print-area tfoot td {
        white-space: nowrap !important;
        word-break: normal !important;
        overflow-wrap: normal !important;
    }

    .no-print,
    .sidebar,
    .topbar,
    .navbar,
    form,
    button,
    .btn {
        display: none !important;
    }

    .print-title {
        display: block !important;
        visibility: visible !important;
        margin-bottom: 10px !important;
        text-align: center !important;
    }

    .print-title h3 {
        font-size: 18px !important;
        margin: 0 0 4px !important;
    }

    .print-title p {
        font-size: 11px !important;
        margin: 0 0 8px !important;
    }
}
</style>


<div class="card guest-panel mb-4">
        <div class="card-header guest-panel-header">Guest Search</div>
        <div class="card-body p-3 p-xl-4">
            <form method="GET" action="{{ route('admin.guests.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="q" class="form-control guest-control" value="{{ $q }}" placeholder="name / guest code / came from">
                </div>
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control guest-control" value="{{ $fromDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control guest-control" value="{{ $toDate }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-dark guest-btn-outline w-100">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header guest-panel-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <span>Guest Details</span>
            <span class="text-muted small">Total Guests: {{ $guests->count() }}</span>
        </div>
        {{-- GUEST_DETAILS_HEADING_FIX_20260609 --}}
        <div class="card-body table-responsive">
            <table class="table table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Department</th>
                        <th>Host Member</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($guests as $guest)
                        <tr>
                            <td>{{ $guest->id }}</td>
                            <td>{{ $guest->guest_code ?: '-' }}</td>
                            <td>{{ optional($guest->date)->format('Y-m-d') ?: '-' }}</td>
                            <td>
                                <div>{{ $guest->name }}</div>
                                @if($guest->remarks)
                                    <div class="small text-muted">{{ $guest->remarks }}</div>
                                @endif
                            </td>
                            <td>{{ $guest->came_from ?: '-' }}</td>
                            <td>{{ is_object($guest->department) ? (($guest->department->code ?? $guest->department->name ?? '-') ?: '-') : (($guest->department ?? '-') ?: '-') }}</td>
                            <td>{{ $guest->hostMember ? ($guest->hostMember->member_code . ' - ' . $guest->hostMember->name) : '-' }}</td>
                            <td>{{ $guest->is_active ? 'Active' : 'Inactive' }}</td>
                            <td>
                                <details>
                                    <summary class="btn btn-sm btn-outline-secondary guest-btn-outline">Edit</summary>
                                    <form method="POST" action="{{ route('admin.guests.edit.legacy', $guest) }}" class="mt-2 row g-2">
                                        @csrf
                                        <div class="col-md-4"><input type="date" name="date" class="form-control guest-control" value="{{ optional($guest->date)->format('Y-m-d') }}"></div>
                                        <div class="col-md-8"><input type="text" name="name" class="form-control guest-control" value="{{ $guest->name }}" required></div>
                                        <div class="col-lg-6 col-12"><input type="text" name="came_from" class="form-control guest-control" value="{{ $guest->came_from }}" placeholder="Company / Came From"></div>
                                        <div class="col-lg-6 col-12"><input type="text" name="remarks" class="form-control guest-control" value="{{ $guest->remarks }}" placeholder="Remarks"></div>
                                        <div class="col-lg-6 col-12">
                                            <select name="department_id" class="form-select guest-control" required>
                                                @foreach($departments as $department)
                                                    <option value="{{ $department->id }}" @selected($guest->department_id === $department->id)>{{ is_object($department) ? ($department->code ?? '') : $department }} - {{ $department->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <select name="host_member_id" class="form-select guest-control">
                                                <option value="">None</option>
                                                @foreach($members as $member)
                                                    <option value="{{ $member->id }}" @selected($guest->host_member_id === $member->id)>{{ $member->member_code }} - {{ $member->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-center">
                                            <div class="form-check">
                                                <input type="hidden" name="is_active" value="0">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="guest-active-{{ $guest->id }}" @checked($guest->is_active)>
                                                <label class="form-check-label" for="guest-active-{{ $guest->id }}">Active</label>
                                            </div>
                                        </div>
                                        <div class="col-12 d-flex gap-2">
                                            <button class="btn btn-sm btn-primary guest-btn-primary">Update</button>
                                        </div>
                                    </form>
                                    <form method="POST" action="{{ route('admin.guests.delete.legacy', $guest) }}" class="mt-2" onsubmit="return confirm('Soft delete this guest?');">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger">Soft Delete</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted">No guest records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card guest-panel h-100">
        <div class="card-header guest-panel-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <span>Guest Meals (Filtered Total: {{ number_format($summary, 2) }})</span>
            <span class="small text-muted">Approval uses rate_type = GUEST by meal date</span>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Guest</th>
                        <th>Department</th>
                        <th>Meal</th>
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Amount</th>
                        <th>Posted</th>
                        <th>Approved</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($meals as $meal)
                        <tr>
                            <td>{{ $meal->id }}</td>
                            <td>{{ optional($meal->meal_date)->format('Y-m-d') }}</td>
                            <td>{{ $meal->guest?->guest_code }} - {{ $meal->guest?->name }}</td>
                            <td>{{ is_object($meal->guest?->department) ? (($meal->guest->department->code ?? $meal->guest->department->name ?? '-') ?: '-') : (($meal->guest?->department ?? '-') ?: '-') }}</td>
                            <td>{{ $meal->meal_type }}</td>
                            <td>{{ $meal->quantity }}</td>
                            <td>{{ $meal->rate_missing ? 'Missing' : number_format((float) $meal->rate_dynamic, 2) }}</td>
                            <td>{{ $meal->rate_missing ? '-' : number_format((float) $meal->amount_dynamic, 2) }}</td>
                            <td>{{ $meal->postedBy?->name ?? $meal->posted_by ?? '-' }}</td>
                            <td>{{ $meal->approved_at ? optional($meal->approved_at)->format('Y-m-d H:i') : 'Draft' }}</td>
                            <td>
                                <details>
                                    <summary class="btn btn-sm btn-outline-secondary">Manage</summary>
                                    <form method="POST" action="{{ route('admin.guests.meals.update.legacy', $meal) }}" class="mt-2 row g-2">
                                        @csrf
                                        <div class="col-md-4">
                                            <input type="date" name="meal_date" class="form-control guest-control" value="{{ optional($meal->meal_date)->format('Y-m-d') }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <select name="guest_id" class="form-select guest-control" required>
                                                @foreach($guests as $guest)
                                                    <option value="{{ $guest->id }}" @selected($meal->guest_id === $guest->id)>{{ $guest->guest_code }} - {{ $guest->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select name="meal_type" class="form-select guest-control" required>
                                                @foreach($mealTypes as $mealType)
                                                    <option value="{{ $mealType }}" @selected($meal->meal_type === $mealType)>{{ $mealType }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" min="1" name="quantity" class="form-control guest-control" value="{{ $meal->quantity }}" required>
                                        </div>
                                        <div class="col-12 d-flex gap-2 flex-wrap">
                                            <button class="btn btn-sm btn-primary guest-btn-primary">Update</button>
                                        </div>
                                    </form>
                                    @if(! $meal->approved_at)
                                        <form method="POST" action="{{ route('admin.guests.meals.approve.legacy', $meal) }}" class="mt-2">
                                            @csrf
                                            <button class="btn btn-sm btn-success">Approve Draft</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.guests.meals.delete.legacy', $meal) }}" class="mt-2" onsubmit="return confirm('Delete this guest meal?');">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted">No guest meals found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection


@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
.ts-wrapper.js-guest-meal-search .ts-control{
    min-height:42px;
    border-radius:10px;
    border-color:#dbe3ef;
}
.ts-dropdown{ z-index:9999; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('select.js-guest-meal-search').forEach(function (el) {
        if (el.tomselect) return;
        new TomSelect(el, {
            create: false,
            allowEmptyOption: true,
            maxOptions: 1000,
            searchField: ['text'],
            dropdownParent: 'body',
            placeholder: 'Search guest by name, company/came from, or code'
        });
    });
});
</script>
@endpush


{{-- Guest print fix: added by audited patch --}}
<link rel="stylesheet" href="{{ asset('css/guest-print-fix.css') }}?v=20260706_120827_120455">
<script src="{{ asset('js/guest-print-fix.js') }}?v=20260706_120827_120455" defer></script>

