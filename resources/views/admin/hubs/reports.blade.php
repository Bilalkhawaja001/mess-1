@extends('layouts.app')

@section('title', 'Reports Hub')
@section('page_title', 'Reports Hub')

@section('content')
<div class="container-fluid">
    <div class="row g-3">
        @if(Route::has('admin.statement.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Statement</h5><p class="text-muted small mb-3">Open existing member statement page.</p><a href="{{ route('admin.statement.index') }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
        @if(Route::has('admin.exports.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Export Center</h5><p class="text-muted small mb-3">Open existing export center and bills export area.</p><a href="{{ route('admin.exports.index') }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
        @if(Route::has('admin.exports.member-ledger'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Member Ledger Export</h5><p class="text-muted small mb-3">Direct existing member ledger export endpoint.</p><a href="{{ route('admin.exports.member-ledger') }}" class="btn btn-outline-primary btn-sm">Open</a></div></div></div>@endif
        @if(Route::has('admin.exports.stock-ledger'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Stock Ledger Export</h5><p class="text-muted small mb-3">Direct existing stock ledger export endpoint.</p><a href="{{ route('admin.exports.stock-ledger') }}" class="btn btn-outline-primary btn-sm">Open</a></div></div></div>@endif
        @if(Route::has('admin.kitchen.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Consumption Report</h5><p class="text-muted small mb-3">Open existing kitchen page on consumption report tab.</p><a href="{{ route('admin.kitchen.index', ['tab' => 'consumption-report']) }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
    </div>
</div>
@endsection
