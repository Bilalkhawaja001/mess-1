@extends('layouts.app')

@section('title', 'Meals Hub')
@section('page_title', 'Meals Hub')

@section('content')
<div class="container-fluid">
    <div class="row g-3">
        @if(Route::has('admin.kitchen.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Kitchen Main</h5><p class="text-muted small mb-3">Open existing kitchen module.</p><a href="{{ route('admin.kitchen.index') }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
        @if(Route::has('admin.kitchen.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Kitchen Issues</h5><p class="text-muted small mb-3">Open existing kitchen issues tab.</p><a href="{{ route('admin.kitchen.index', ['tab' => 'issues']) }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
        @if(Route::has('admin.kitchen.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Menu</h5><p class="text-muted small mb-3">Open existing kitchen menu tab.</p><a href="{{ route('admin.kitchen.index', ['tab' => 'menu']) }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
        @if(Route::has('admin.kitchen.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Meal Plans</h5><p class="text-muted small mb-3">Open existing meal plans tab.</p><a href="{{ route('admin.kitchen.index', ['tab' => 'plans']) }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
        @if(Route::has('admin.kitchen.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Consumption Report</h5><p class="text-muted small mb-3">Open existing consumption report tab.</p><a href="{{ route('admin.kitchen.index', ['tab' => 'consumption-report']) }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
        @if(Route::has('admin.guests.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Guest Meals</h5><p class="text-muted small mb-3">Open existing guests module for guest meal handling.</p><a href="{{ route('admin.guests.index') }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
    </div>
</div>
@endsection
