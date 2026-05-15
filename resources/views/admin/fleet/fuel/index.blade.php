@extends('layouts.app')

@section('content')
<div class="fleet-page">
    <div class="fleet-header">
        <h1>Fuel Logs</h1>
        <a href="{{ route('admin.fleet.fuel.create') }}" class="btn btn-primary">Add New</a>
    </div>

    <form method="GET" class="fleet-filter">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search">
        <button class="btn btn-secondary">Filter</button>
        <a href="{{ route('admin.fleet.fuel.index') }}" class="btn btn-light">Reset</a>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Date</th><th>Vehicle</th><th>Driver</th><th>Liters</th><th>Amount</th><th>KM/L</th><th>Alert</th></tr></thead>
                <tbody>
                    @forelse($fuelLogs as $row)<tr><td>{{ optional($row->fuel_date)->format('d-M-Y') }}</td><td>{{ $row->vehicle->vehicle_no ?? '-' }}</td><td>{{ $row->driver->name ?? '-' }}</td><td>{{ $row->liters }}</td><td>{{ number_format($row->total_amount) }}</td><td>{{ $row->average_km_per_liter }}</td><td>{{ $row->is_abnormal_average ? 'Abnormal' : 'OK' }}</td></tr>@empty<tr><td colspan="7">No fuel logs.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
