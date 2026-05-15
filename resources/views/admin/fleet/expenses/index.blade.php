@extends('layouts.app')

@section('content')
<div class="container-fluid py-3 fleet-page">
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-0">Fleet Expenses</h1>
            <small class="text-muted">Read-only expense ledger generated from fuel and maintenance activity.</small>
        </div>
        @if(Route::has('admin.fleet.reports.export'))
            <a href="{{ route('admin.fleet.reports.export', ['report' => 'vehicle_monthly_cost']) }}" class="btn btn-outline-dark">Export Cost Report</a>
        @endif
    </div>

    <form method="GET" class="card shadow-sm mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-2"><label class="form-label">From</label><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">To</label><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Vehicle ID</label><input type="number" name="vehicle_id" value="{{ request('vehicle_id') }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Driver ID</label><input type="number" name="driver_id" value="{{ request('driver_id') }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Category</label><input type="text" name="category" value="{{ request('category') }}" class="form-control" placeholder="Fuel / Maintenance"></div>
            <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary flex-fill">Filter</button><a href="{{ route('admin.fleet.expenses.index') }}" class="btn btn-light">Reset</a></div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Date</th><th>Vehicle</th><th>Driver</th><th>Category</th><th>Description</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                    @forelse($expenses as $expense)
                        <tr>
                            <td>{{ optional($expense->expense_date)->format('d-M-Y') }}</td>
                            <td>{{ $expense->vehicle->vehicle_no ?? '-' }}</td>
                            <td>{{ $expense->driver->name ?? '-' }}</td>
                            <td>{{ $expense->category }}</td>
                            <td>{{ $expense->description ?? '-' }}</td>
                            <td class="text-end">{{ number_format((float) $expense->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">No fleet expenses found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($expenses, 'links'))
            <div class="card-footer">{{ $expenses->links() }}</div>
        @endif
    </div>
</div>
@endsection
