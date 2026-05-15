@extends('layouts.app')

@section('content')
<div class="container-fluid py-3 fleet-page">
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-0">Fleet Reports</h1>
            <small class="text-muted">Filter, review and export fuel, maintenance, trips, documents, incidents and challans.</small>
        </div>
        @if(Route::has('admin.fleet.reports.export'))
            <a href="{{ route('admin.fleet.reports.export', request()->query() + ['report' => 'vehicle_fuel_average']) }}" class="btn btn-dark">Export Fuel Average CSV</a>
        @endif
    </div>

    <form method="GET" class="card shadow-sm mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-2"><label class="form-label">From</label><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">To</label><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Vehicle ID</label><input type="number" name="vehicle_id" value="{{ request('vehicle_id') }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Driver ID</label><input type="number" name="driver_id" value="{{ request('driver_id') }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Status</label><input type="text" name="status" value="{{ request('status') }}" class="form-control"></div>
            <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary flex-fill">Filter</button><a href="{{ route('admin.fleet.reports.index') }}" class="btn btn-light">Reset</a></div>
        </div>
    </form>

    <div class="row g-3 mb-3">
        @foreach(($summary ?? []) as $label => $value)
            <div class="col-sm-6 col-xl-2"><div class="card shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase">{{ str_replace('_', ' ', $label) }}</small><h4 class="mb-0">{{ number_format((float) $value, 2) }}</h4></div></div></div>
        @endforeach
    </div>

    @php
        $titles = [
            'vehicle_fuel_average' => 'Vehicle-wise Fuel Average',
            'driver_fuel_average' => 'Driver-wise Fuel Average',
            'vehicle_monthly_cost' => 'Vehicle Monthly Cost',
            'maintenance_cost_by_vehicle' => 'Maintenance Cost by Vehicle',
            'document_expiry' => 'Document Expiry Report',
            'trip_utilization' => 'Trip Utilization Report',
            'tyre_battery_lifecycle' => 'Tyre/Battery Lifecycle Report',
            'challan_fines' => 'Challan/Fine Report',
            'incident_cost' => 'Incident Cost Report',
        ];
    @endphp

    @foreach(($catalog ?? []) as $key => $rows)
        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <strong>{{ $titles[$key] ?? str_replace('_', ' ', $key) }}</strong>
                @if(Route::has('admin.fleet.reports.export'))
                    <a class="btn btn-sm btn-outline-dark" href="{{ route('admin.fleet.reports.export', request()->query() + ['report' => $key]) }}">Export CSV</a>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            @foreach(($exportColumns[$key] ?? []) as $column)
                                <th>{{ str_replace('_', ' ', $column) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                @foreach(($exportColumns[$key] ?? []) as $column)
                                    <td>{{ is_numeric(data_get($row, $column)) ? number_format((float) data_get($row, $column), 2) : data_get($row, $column) }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ max(count($exportColumns[$key] ?? []), 1) }}" class="text-muted">No records found for selected filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
@endsection
