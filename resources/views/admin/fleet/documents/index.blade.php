@extends('layouts.app')

@section('content')
<div class="fleet-page">
    <div class="fleet-header">
        <h1>Documents</h1>
        <a href="{{ route('admin.fleet.documents.create') }}" class="btn btn-primary">Add New</a>
    </div>

    <form method="GET" class="fleet-filter">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search">
        <button class="btn btn-secondary">Filter</button>
        <a href="{{ route('admin.fleet.documents.index') }}" class="btn btn-light">Reset</a>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Vehicle</th><th>Document</th><th>No</th><th>Expiry</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($documents as $doc)<tr><td>{{ $doc->vehicle->vehicle_no ?? '-' }}</td><td>{{ $doc->document_type }}</td><td>{{ $doc->document_no }}</td><td>{{ optional($doc->expiry_date)->format('d-M-Y') }}</td><td>{{ $doc->status }}</td><td><a href="{{ route('admin.fleet.documents.show',$doc) }}">View</a></td></tr>@empty<tr><td colspan="6">No documents.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
