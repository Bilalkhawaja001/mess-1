@extends('layouts.app')

@section('title', 'Mess Costing')
@section('page_title', 'Mess Costing & Bill System')

@section('content')
<div class="container-fluid">
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Member Count Basis</div><div class="fs-4 fw-bold">{{ $preview['member_count'] ?? 0 }}</div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Active Days Basis</div><div class="fs-4 fw-bold">{{ number_format((float)($preview['active_days_total'] ?? 0), 3) }}</div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Attendance Rows</div><div class="fs-4 fw-bold">{{ $preview['attendance_rows'] ?? 0 }}</div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Department Support</div><div class="fs-6 fw-bold">{{ implode(', ', $preview['department_names'] ?? []) ?: 'N/A' }}</div></div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header"><h5 class="mb-0">Monthly Costing Dashboard</h5></div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.mess-costing.index') }}" class="row g-2 mb-4">
                        <div class="col-md-4"><label class="form-label">Month</label><input type="month" name="month_cycle" class="form-control" value="{{ $monthCycle }}"></div>
                        <div class="col-md-5"><label class="form-label">Mess / Section</label><select name="mess_id" class="form-select"><option value="">All</option>@foreach($messes as $mess)<option value="{{ $mess->id }}" @selected((string)$messId === (string)$mess->id)>{{ $mess->name }}</option>@endforeach</select></div>
                        <div class="col-md-3 d-grid align-self-end"><button class="btn btn-outline-primary">Refresh Basis</button></div>
                    </form>

                    <form method="POST" action="{{ route('admin.mess-costing.store') }}" class="row g-3">
                        @csrf
                        <input type="hidden" name="month_cycle" value="{{ $monthCycle }}">
                        <input type="hidden" name="mess_id" value="{{ $messId }}">
                        <div class="col-md-6"><label class="form-label">Food Cost</label><input type="number" step="0.01" min="0" name="food_cost" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Other Expense</label><input type="number" step="0.01" min="0" name="other_cost" class="form-control" value="0"></div>
                        <div class="col-md-3 form-check ms-2"><input class="form-check-input" type="checkbox" name="include_gas_cost" value="1" id="include_gas_cost" checked><label class="form-check-label" for="include_gas_cost">Include Gas Cost</label></div>
                        <div class="col-md-8"><label class="form-label">Gas Cost</label><input type="number" step="0.01" min="0" name="gas_cost" class="form-control" value="0"></div>
                        <div class="col-md-3 form-check ms-2"><input class="form-check-input" type="checkbox" name="include_salary_cost" value="1" id="include_salary_cost" checked><label class="form-check-label" for="include_salary_cost">Include Salary Cost</label></div>
                        <div class="col-md-8"><label class="form-label">Salary Cost</label><input type="number" step="0.01" min="0" name="salary_cost" class="form-control" value="0"></div>
                        <div class="col-12"><button class="btn btn-primary">Save Costing Snapshot</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header"><h5 class="mb-0">Saved Costing History</h5></div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($history as $costing)
                            <a href="{{ route('admin.mess-costing.show', $costing) }}" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between"><strong>{{ $costing->month_cycle }}</strong><span>{{ number_format((float)$costing->total_cost, 2) }}</span></div>
                                <div class="small text-muted">{{ optional($costing->mess)->name ?? 'All Messes' }} | Members: {{ $costing->member_count }}</div>
                            </a>
                        @empty
                            <div class="list-group-item text-muted">No saved costing snapshots yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
