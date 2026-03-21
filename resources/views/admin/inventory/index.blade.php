@extends('layouts.app')

@section('title', 'Inventory')
@section('page_title', 'Inventory')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-header">Legacy Bulk Import (name,sku,uom,reorder_level,is_active,category)</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.inventory.items.import') }}" enctype="multipart/form-data" class="row g-2">
            @csrf
            <div class="col-md-6"><input type="file" name="file" class="form-control" accept=".csv,.txt" required></div>
            <div class="col-md-3"><button class="btn btn-outline-primary">Import Items CSV</button></div>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Manual Item Create</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.inventory.items.store') }}" class="row g-2">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label">ItemCode</label>
                        <input type="text" name="item_code" class="form-control" value="{{ old('item_code') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">ItemName</label>
                        <input type="text" name="item_name" class="form-control" value="{{ old('item_name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <input type="text" name="category" class="form-control" value="{{ old('category') }}" placeholder="e.g. Grocery">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">UoM</label>
                        <input type="text" name="uom" class="form-control" value="{{ old('uom', 'kg') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Reorder Level</label>
                        <input type="number" name="reorder_level" class="form-control" value="{{ old('reorder_level', 0) }}" min="0" step="0.001">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">Create Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Bulk Items Upload (CSV)</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.inventory.items.bulk-upload') }}" enctype="multipart/form-data" class="row g-2">
                    @csrf
                    <div class="col-12">
                        <label class="form-label">CSV File</label>
                        <input type="file" name="items_file" class="form-control" accept=".csv,text/csv" required>
                        <small class="text-muted">Required headers: <strong>ItemCode, ItemName, Category, UoM</strong></small>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-success" type="submit">Upload CSV</button>
                    </div>
                </form>
                <hr>
                <div class="small text-muted">
                    <div><strong>Sample Row:</strong></div>
                    <code>ITM-001, Rice Super, Grocery, kg</code>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Items List</h5>
                <span class="badge bg-secondary">{{ $items->count() }} items</span>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ItemCode</th>
                            <th>ItemName</th>
                            <th>Category</th>
                            <th>UoM</th>
                            <th>Reorder</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>{{ $item->sku }}</td>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->category ?? 'Uncategorized' }}</td>
                                <td>{{ $item->uom }}</td>
                                <td>{{ number_format((float) $item->reorder_level, 3) }}</td>
                                <td>
                                    @if($item->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No items found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
