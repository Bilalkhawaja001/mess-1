@extends('layouts.app')

@section('title', 'Inventory Hub')
@section('page_title', 'Inventory Hub')

@section('content')
<div class="container-fluid">
    <div class="row g-3">
        @if(Route::has('admin.inventory.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Inventory Main</h5><p class="text-muted small mb-3">Open existing inventory module.</p><a href="{{ route('admin.inventory.index') }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
        @if(Route::has('admin.inventory.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Items</h5><p class="text-muted small mb-3">Open existing items tab.</p><a href="{{ route('admin.inventory.index', ['tab' => 'items']) }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
        @if(Route::has('admin.inventory.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Store Stock</h5><p class="text-muted small mb-3">Open existing store stock tab.</p><a href="{{ route('admin.inventory.index', ['tab' => 'store-stock']) }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
        @if(Route::has('admin.inventory.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Stock Ledger</h5><p class="text-muted small mb-3">Open existing stock ledger tab.</p><a href="{{ route('admin.inventory.index', ['tab' => 'stock-ledger']) }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
        @if(Route::has('admin.inventory.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Stock Count</h5><p class="text-muted small mb-3">Open existing stock count tab.</p><a href="{{ route('admin.inventory.index', ['tab' => 'stock-count']) }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
        @if(Route::has('admin.inventory.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Vendor Return</h5><p class="text-muted small mb-3">Open existing vendor return tab.</p><a href="{{ route('admin.inventory.index', ['tab' => 'vendor-return']) }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
        @if(Route::has('admin.procurement.index'))<div class="col-md-6 col-xl-4"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Vendors / PO / GRN</h5><p class="text-muted small mb-3">Open existing procurement area for vendors, purchase orders, and GRN.</p><a href="{{ route('admin.procurement.index') }}" class="btn btn-primary btn-sm">Open</a></div></div></div>@endif
    </div>
</div>
@endsection
