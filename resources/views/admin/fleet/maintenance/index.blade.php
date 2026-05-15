@extends('layouts.app')

@section('content')
<div class="fleet-page">
    <div class="fleet-header">
        <h1>Maintenance</h1>
        <a href="{{ route('admin.fleet.maintenance.create') }}" class="btn btn-primary">Add New</a>
    </div>

    <form method="GET" class="fleet-filter">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search">
        <button class="btn btn-secondary">Filter</button>
        <a href="{{ route('admin.fleet.maintenance.index') }}" class="btn btn-light">Reset</a>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Date</th><th>Vehicle</th><th>Type</th><th>Workshop</th><th>Cost</th><th>Status</th><th>Due</th></tr></thead>
                <tbody>
                    @forelse($logs as $row)<tr><td>{{ optional($row->maintenance_date)->format('d-M-Y') }}</td><td>{{ $row->vehicle->vehicle_no ?? '-' }}</td><td>{{ $row->maintenance_type }}</td><td>{{ $row->workshop }}</td><td>{{ number_format($row->total_cost) }}</td><td>{{ $row->status }}</td><td>{{ $row->is_overdue ? 'Overdue' : 'OK' }}</td></tr>@empty<tr><td colspan="7">No maintenance logs.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
