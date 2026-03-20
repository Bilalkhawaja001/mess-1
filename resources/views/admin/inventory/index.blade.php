@extends('layouts.app')

@section('title','Inventory')
@section('page_title','Inventory')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-header">Bulk Import Items</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.inventory.items.import') }}" enctype="multipart/form-data" class="row g-2">
            @csrf
            <div class="col-md-6"><input type="file" name="file" class="form-control" accept=".csv,.txt" required></div>
            <div class="col-md-3"><button class="btn btn-outline-primary">Import Items CSV</button></div>
            <div class="col-12 text-muted small">Headers: name,sku,uom,reorder_level,is_active</div>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header">Items ({{ $items->count() }})</div>
    <div class="card-body table-responsive">
        <table class="table table-sm">
            <thead><tr><th>ID</th><th>Name</th><th>SKU</th><th>UOM</th><th>Reorder Level</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->sku }}</td>
                    <td>{{ $item->uom }}</td>
                    <td>{{ $item->reorder_level }}</td>
                    <td>{{ $item->is_active ? 'Active' : 'Inactive' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Recent Stock Transactions</div>
    <div class="card-body table-responsive">
        <table class="table table-sm">
            <thead><tr><th>Date</th><th>Item ID</th><th>Type</th><th>Qty</th><th>Unit Cost</th><th>Remarks</th></tr></thead>
            <tbody>
            @foreach($ledger as $tx)
                <tr>
                    <td>{{ $tx->txn_at }}</td>
                    <td>{{ $tx->item_id }}</td>
                    <td>{{ $tx->txn_type }}</td>
                    <td>{{ $tx->quantity }}</td>
                    <td>{{ $tx->unit_cost }}</td>
                    <td>{{ $tx->remarks }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
