@extends('layouts.app')

@section('title', 'Mess Costing Detail')
@section('page_title', 'Mess Costing Detail')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">{{ $costing->month_cycle }} | {{ optional($costing->mess)->name ?? 'All Messes' }}</h4>
            <div class="text-muted small">Saved snapshot only. No billing, ledger, or deduction entries created.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.mess-costing.print', $costing) }}" class="btn btn-outline-secondary btn-sm">Print</a>
            <a href="{{ route('admin.mess-costing.export', $costing) }}" class="btn btn-outline-primary btn-sm">Export CSV</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="small text-muted">Total Cost</div><div class="fs-4 fw-bold">{{ number_format((float)$costing->total_cost, 2) }}</div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="small text-muted">Member Count</div><div class="fs-4 fw-bold">{{ $costing->member_count }}</div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="small text-muted">Cost / Member</div><div class="fs-4 fw-bold">{{ number_format((float)$costing->cost_per_member, 2) }}</div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="small text-muted">Cost / Day</div><div class="fs-4 fw-bold">{{ number_format((float)$costing->cost_per_day, 4) }}</div></div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm"><div class="card-header"><h5 class="mb-0">Allocation Summary</h5></div><div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th>Food Cost</th><td>{{ number_format((float)$costing->food_cost, 2) }}</td></tr>
                    <tr><th>Gas Cost</th><td>{{ $costing->include_gas_cost ? number_format((float)$costing->gas_cost, 2) : 'Excluded' }}</td></tr>
                    <tr><th>Salary Cost</th><td>{{ $costing->include_salary_cost ? number_format((float)$costing->salary_cost, 2) : 'Excluded' }}</td></tr>
                    <tr><th>Other Expense</th><td>{{ number_format((float)$costing->other_cost, 2) }}</td></tr>
                    <tr class="fw-bold"><th>Total Cost</th><td>{{ number_format((float)$costing->total_cost, 2) }}</td></tr>
                    <tr><th>Active Days Basis</th><td>{{ number_format((float)$costing->active_days_total, 3) }}</td></tr>
                </table>
            </div></div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm"><div class="card-header"><h5 class="mb-0">Comparison</h5></div><div class="card-body">
                @if(!empty($costing->comparison_json))
                    <table class="table table-sm mb-0"><thead><tr><th>Section</th><th>Members</th></tr></thead><tbody>@foreach($costing->comparison_json as $row)<tr><td>{{ $row['name'] ?? '' }}</td><td>{{ $row['member_count'] ?? 0 }}</td></tr>@endforeach</tbody></table>
                @else
                    <div class="text-muted small">Comparison skipped because safe Admin/Spinning/Weaving support was not available from existing data.</div>
                @endif
            </div></div>
        </div>
    </div>
</div>
@endsection
