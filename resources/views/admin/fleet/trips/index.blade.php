@extends('layouts.app')

@section('content')
<div class="fleet-page">
    <div class="fleet-header">
        <h1>Trips</h1>
        <a href="{{ route('admin.fleet.trips.create') }}" class="btn btn-primary">Add New</a>
    </div>

    <form method="GET" class="fleet-filter">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search">
        <button class="btn btn-secondary">Filter</button>
        <a href="{{ route('admin.fleet.trips.index') }}" class="btn btn-light">Reset</a>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Date</th><th>Vehicle</th><th>Driver</th><th>From</th><th>To</th><th>Distance</th></tr></thead>
                <tbody>
                    @forelse($trips as $trip)<tr><td>{{ optional($trip->trip_date)->format('d-M-Y') }}</td><td>{{ $trip->vehicle->vehicle_no ?? '-' }}</td><td>{{ $trip->driver->name ?? '-' }}</td><td>{{ $trip->from_location }}</td><td>{{ $trip->to_location }}</td><td>{{ $trip->distance }}</td></tr>@empty<tr><td colspan="6">No trips.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
