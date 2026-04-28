@extends('layouts.app')

@section('title', 'Operations Hub')
@section('page_title', 'Operations Hub')

@section('content')
<div class="container-fluid">
    <div class="row g-3">
        <div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Dashboard</h5><p class="text-muted small mb-3">System overview and primary admin landing page.</p><a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-sm">Open</a></div></div></div>
        @if(Route::has('admin.members.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Members</h5><p class="text-muted small mb-3">Open existing member management module.</p><a href="{{ route('admin.members.index') }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
        @if(Route::has('admin.attendance.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Attendance</h5><p class="text-muted small mb-3">Open existing attendance workflow.</p><a href="{{ route('admin.attendance.index') }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
        @if(Route::has('admin.attendance-monthly.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Monthly Attendance</h5><p class="text-muted small mb-3">Open existing monthly attendance screen.</p><a href="{{ route('admin.attendance-monthly.index') }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
        @if(Route::has('admin.complaints.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Complaints</h5><p class="text-muted small mb-3">Open existing complaints list.</p><a href="{{ route('admin.complaints.index') }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
        @if(Route::has('admin.guests.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Guests</h5><p class="text-muted small mb-3">Open existing guest records and meal area.</p><a href="{{ route('admin.guests.index') }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
    </div>
</div>
@endsection
