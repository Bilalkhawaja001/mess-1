@extends('layouts.app')

@section('content')
<div class="fleet-page">
    <div class="fleet-header">
        <h1>Drivers</h1>
        <a href="{{ route('admin.fleet.drivers.create') }}" class="btn btn-primary">Add New</a>
    </div>

    <form method="GET" class="fleet-filter">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search">
        <button class="btn btn-secondary">Filter</button>
        <a href="{{ route('admin.fleet.drivers.index') }}" class="btn btn-light">Reset</a>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Name</th><th>Code</th><th>Mobile</th><th>License Expiry</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($drivers as $driver)<tr><td>{{ $driver->name }}</td><td>{{ $driver->employee_code }}</td><td>{{ $driver->mobile_no }}</td><td>{{ optional($driver->license_expiry_date)->format('d-M-Y') }}</td><td>{{ $driver->status }}</td><td><a href="{{ route('admin.fleet.drivers.show',$driver) }}">View</a></td></tr>@empty<tr><td colspan="6">No drivers found.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
