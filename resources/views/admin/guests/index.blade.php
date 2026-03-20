@extends('layouts.app')

@section('title','Guest Management')
@section('page_title','Guest Management')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header">Bulk Import Guests</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.guests.import') }}" enctype="multipart/form-data" class="row g-2">
                    @csrf
                    <div class="col-12"><input type="file" name="file" class="form-control" accept=".csv,.txt" required></div>
                    <div class="col-12"><button class="btn btn-outline-primary">Import Guests CSV</button></div>
                    <div class="col-12 text-muted small">Headers: name,contact,department,is_active</div>
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
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-outline-primary">Import Meals CSV</button>
                        <a href="{{ route('admin.guests.meals.export') }}" class="btn btn-outline-secondary">Export Meals</a>
                    </div>
                    <div class="col-12 text-muted small">Headers: guest_id,meal_date,meal_type,quantity,rate</div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header">Guests ({{ $guests->count() }})</div>
    <div class="card-body table-responsive">
        <table class="table table-sm">
            <thead><tr><th>ID</th><th>Name</th><th>Contact</th><th>Department</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($guests as $guest)
                <tr>
                    <td>{{ $guest->id }}</td>
                    <td>{{ $guest->name }}</td>
                    <td>{{ $guest->contact ?: '-' }}</td>
                    <td>{{ $guest->department ?: '-' }}</td>
                    <td>{{ $guest->is_active ? 'Active' : 'Inactive' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Recent Guest Meals (Total Amount: {{ number_format($summary, 2) }})</div>
    <div class="card-body table-responsive">
        <table class="table table-sm">
            <thead><tr><th>Date</th><th>Guest ID</th><th>Type</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead>
            <tbody>
            @foreach($meals as $meal)
                <tr>
                    <td>{{ $meal->meal_date }}</td>
                    <td>{{ $meal->guest_id }}</td>
                    <td>{{ $meal->meal_type }}</td>
                    <td>{{ $meal->quantity }}</td>
                    <td>{{ $meal->rate }}</td>
                    <td>{{ $meal->amount }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
