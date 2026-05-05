@extends('layouts.app')

@section('title', 'Guest Management')
@section('page_title', 'Guest Management')

@section('content')
<div class="container-fluid px-0">
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

    <div class="row g-3 mb-3">
        <div class="col-xl-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Create Guest</span>
                    <span class="text-muted small">Today Guest Rate: {{ $currentRate !== null ? number_format($currentRate, 2) : 'Not configured' }}</span>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.guests.store') }}" class="row g-2">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">Guest Code</label>
                            <input type="text" name="guest_code" class="form-control" placeholder="Auto G00001">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control" value="{{ $today }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Host Member</label>
                            <select name="host_member_id" class="form-select">
                                <option value="">None</option>
                                @foreach($members as $member)
                                    <option value="{{ $member->id }}">{{ $member->member_code }} - {{ $member->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company / Came From</label>
                            <input type="text" name="came_from" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select" required>
                                <option value="">Select department</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}">{{ is_object($department) ? ($department->code ?? '') : $department }} - {{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Remarks</label>
                            <input type="text" name="remarks" class="form-control">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary">Save Guest</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">Create Guest Meal Draft</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.guests.meals.store') }}" class="row g-2">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label">Guest</label>
                            <select name="guest_id" class="form-select js-guest-meal-search" required>
                                <option value="">Select guest</option>
                                @foreach($guests as $guest)
                                    <option value="{{ $guest->id }}">{{ $guest->name ?? "Guest" }} — {{ $guest->came_from ?? "-" }} — {{ $guest->guest_code ?? "" }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="meal_date" class="form-control" value="{{ $today }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Meal Type</label>
                            <select name="meal_type" class="form-select" required>
                                @foreach($mealTypes as $mealType)
                                    <option value="{{ $mealType }}">{{ $mealType }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Qty</label>
                            <input type="number" min="1" name="quantity" class="form-control" value="1" required>
                        </div>
                        <div class="col-md-9 d-flex align-items-end">
                            <div class="small text-muted">Draft save keeps rate/amount zero until edit/approve, matching Flask flow.</div>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary">Save Meal Draft</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">Bulk Import Guests</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.guests.import') }}" enctype="multipart/form-data" class="row g-2">
                        @csrf
                        <div class="col-12"><input type="file" name="file" class="form-control" accept=".csv,.txt" required></div>
                        <div class="col-12"><button class="btn btn-outline-primary">Import Guests CSV</button></div>
                        <div class="col-12 text-muted small">Headers: guest_code,date,name,came_from/company,remarks,department_id/department_code/department,host_member_id/host_member_code,is_active,is_deleted</div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">Bulk Import Guest Meals</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.guests.meals.import') }}" enctype="multipart/form-data" class="row g-2">
                        @csrf
                        <div class="col-12"><input type="file" name="file" class="form-control" accept=".csv,.txt" required></div>
                        <div class="col-12 d-flex gap-2 flex-wrap">
                            <button class="btn btn-outline-primary">Import Meals CSV</button>
                            <a href="{{ route('admin.guests.meals.export', ['from' => $fromDate, 'to' => $toDate]) }}" class="btn btn-outline-secondary">Export Meals</a>
                        </div>
                        <div class="col-12 text-muted small">Headers: guest_id/guest_code,date/meal_date,meal_type,qty/quantity</div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    

{{-- Guest Meal Printable Report --}}
@php
    $gmFrom = request('from_date') ?: request('gm_from');
    $gmTo = request('to_date') ?: request('gm_to');

    $guestMealReportQuery = \Illuminate\Support\Facades\DB::table('guest_meals')
        ->leftJoin('guests', 'guest_meals.guest_id', '=', 'guests.id')
        ->select(
            'guest_meals.id',
            'guest_meals.meal_date',
            'guest_meals.meal_type',
            'guest_meals.quantity',
            'guest_meals.rate',
            'guest_meals.rate_applied',
            'guest_meals.amount',
            'guests.name as guest_name',
            'guests.came_from as guest_came_from',
            'guests.guest_code as guest_code'
        );

    if ($gmFrom) {
        $guestMealReportQuery->whereDate('guest_meals.meal_date', '>=', $gmFrom);
    }

    if ($gmTo) {
        $guestMealReportQuery->whereDate('guest_meals.meal_date', '<=', $gmTo);
    }

    $guestMealReportRows = $guestMealReportQuery
        ->orderBy('guest_meals.meal_date')
        ->orderBy('guests.name')
        ->orderBy('guest_meals.id')
        ->limit(1000)
        ->get();

    $guestMealReportQtyTotal = $guestMealReportRows->sum(function ($row) {
        return (float) ($row->quantity ?? 0);
    });

    $guestMealReportGrandTotal = $guestMealReportRows->sum(function ($row) {
        return (float) ($row->amount ?? 0);
    });
@endphp

<div class="card shadow-sm mb-3 guest-meal-report-print-area">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <strong>Guest Meal Print Report</strong>
            <div class="small text-muted">
                {{ $gmFrom ?: 'All dates' }} to {{ $gmTo ?: 'All dates' }}
            </div>
        </div>

        <form method="GET" class="d-flex flex-wrap gap-2 align-items-end no-print">
            <div>
                <label class="form-label small mb-1">From Date</label>
                <input type="date" name="from_date" value="{{ $gmFrom }}" class="form-control form-control-sm">
            </div>
            <div>
                <label class="form-label small mb-1">To Date</label>
                <input type="date" name="to_date" value="{{ $gmTo }}" class="form-control form-control-sm">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm">Print</button>
        </form>
    </div>

    <div class="card-body">
        <div class="print-title d-none">
            <h3 class="mb-1">Guest Meal Report</h3>
            <p class="mb-2">Date: {{ $gmFrom ?: 'All' }} to {{ $gmTo ?: 'All' }}</p>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Meal Date</th>
                        <th>Guest Name</th>
                        <th>Company/Came From</th>
                        <th>Meal Type</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Rate</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($guestMealReportRows as $row)
                        @php
                            $displayRate = $row->rate ?? $row->rate_applied ?? 0;
                        @endphp
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($row->meal_date)->format('d-M-Y') }}</td>
                            <td>
                                {{ $row->guest_name ?: '-' }}
                                @if($row->guest_code)
                                    <span class="text-muted small">({{ $row->guest_code }})</span>
                                @endif
                            </td>
                            <td>{{ $row->guest_came_from ?: '-' }}</td>
                            <td>{{ $row->meal_type ?: '-' }}</td>
                            <td class="text-end">{{ number_format((float) $row->quantity, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $displayRate, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $row->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No guest meal records found.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="fw-bold table-light">
                        <td colspan="4" class="text-end">Grand Total</td>
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
@media print {
    body * {
        visibility: hidden !important;
    }
    .guest-meal-report-print-area,
    .guest-meal-report-print-area * {
        visibility: visible !important;
    }
    .guest-meal-report-print-area {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        border: 0 !important;
        box-shadow: none !important;
    }
    .no-print,
    .sidebar,
    .topbar,
    .navbar {
        display: none !important;
    }
    .print-title {
        display: block !important;
    }
}
</style>


<div class="card shadow-sm mb-3">
        <div class="card-header">Guest Search</div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.guests.index') }}" class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="name / guest code / came from">
                </div>
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-dark w-100">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header">Guests ({{ $guests->count() }})</div>
        <div class="card-body table-responsive">
            <table class="table table-sm align-middle">
                <thead>
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
                                    <summary class="btn btn-sm btn-outline-secondary">Edit</summary>
                                    <form method="POST" action="{{ route('admin.guests.edit.legacy', $guest) }}" class="mt-2 row g-2">
                                        @csrf
                                        <div class="col-md-4"><input type="date" name="date" class="form-control" value="{{ optional($guest->date)->format('Y-m-d') }}"></div>
                                        <div class="col-md-8"><input type="text" name="name" class="form-control" value="{{ $guest->name }}" required></div>
                                        <div class="col-md-6"><input type="text" name="came_from" class="form-control" value="{{ $guest->came_from }}" placeholder="Company / Came From"></div>
                                        <div class="col-md-6"><input type="text" name="remarks" class="form-control" value="{{ $guest->remarks }}" placeholder="Remarks"></div>
                                        <div class="col-md-6">
                                            <select name="department_id" class="form-select" required>
                                                @foreach($departments as $department)
                                                    <option value="{{ $department->id }}" @selected($guest->department_id === $department->id)>{{ is_object($department) ? ($department->code ?? '') : $department }} - {{ $department->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <select name="host_member_id" class="form-select">
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
                                            <button class="btn btn-sm btn-primary">Update</button>
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

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
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
                                            <input type="date" name="meal_date" class="form-control" value="{{ optional($meal->meal_date)->format('Y-m-d') }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <select name="guest_id" class="form-select" required>
                                                @foreach($guests as $guest)
                                                    <option value="{{ $guest->id }}" @selected($meal->guest_id === $guest->id)>{{ $guest->guest_code }} - {{ $guest->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select name="meal_type" class="form-select" required>
                                                @foreach($mealTypes as $mealType)
                                                    <option value="{{ $mealType }}" @selected($meal->meal_type === $mealType)>{{ $mealType }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" min="1" name="quantity" class="form-control" value="{{ $meal->quantity }}" required>
                                        </div>
                                        <div class="col-12 d-flex gap-2 flex-wrap">
                                            <button class="btn btn-sm btn-primary">Update</button>
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
            placeholder: 'Search guest by name, company/came from, or code'
        });
    });
});
</script>
@endpush

