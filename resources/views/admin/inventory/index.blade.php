@extends('layouts.app')

@section('title', 'Items / Store')
@section('page_title', 'Items / Store')

@push('styles')
<style>
    .inventory-page-wrap {
        position: relative;
        z-index: 1;
        overflow: visible;
    }

    .inventory-page-wrap .card,
    .inventory-page-wrap .table-responsive,
    .inventory-page-wrap .card-body,
    .inventory-page-wrap .card-header {
        position: relative;
        z-index: 1;
        background-image: none !important;
    }

    .inventory-tab-nav {
        gap: 8px;
    }

    .inventory-tab-nav .nav-link {
        border-radius: 999px;
    }

    .inventory-stat {
        font-size: 0.85rem;
        color: #64748b;
    }

    .inventory-header-title {
        font-size: 1.05rem;
        font-weight: 700;
    }

    .inventory-header-sub {
        font-size: 0.8rem;
    }

    .inventory-header-badges .badge {
        font-size: 0.72rem;
        padding: 0.25rem 0.55rem;
    }

    .inventory-tab-nav .nav-link {
        padding-inline: 0.9rem;
        padding-block: 0.35rem;
        font-size: 0.82rem;
    }

    .inventory-import-shell .card-header,
    .inventory-import-shell .card-body {
        padding-top: 0.7rem;
        padding-bottom: 0.7rem;
    }

    .inventory-items-table .card-header {
        padding-top: 0.7rem;
        padding-bottom: 0.55rem;
    }

    .inventory-manual-txn .card-header {
        padding-top: 0.7rem;
        padding-bottom: 0.55rem;
    }

    .inventory-vendor-return .card-header {
        padding-top: 0.7rem;
        padding-bottom: 0.55rem;
    }
</style>
@endpush

@section('content')
<div class="inventory-page-wrap">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <div class="inventory-header-title mb-1">Items / Store</div>
            <div class="inventory-header-sub inventory-stat">Item master and live store stock separated without changing route structure.</div>
        </div>
        <div class="d-flex align-items-center gap-2 inventory-header-badges">
            <span class="badge bg-danger">{{ count($lowStockItems ?? []) }} low stock</span>
            <span class="badge bg-secondary">{{ $items->count() }} items</span>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.inventory.index') }}" class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-lg-7">
                    <label class="form-label">Search inventory</label>
                    <input type="text" name="q" class="form-control" value="{{ $search ?? '' }}" placeholder="Search by item name, code, category, store stock, vendor, GRN, reference">
                </div>
                <div class="col-lg-3">
                    <label class="form-label">Keep tab</label>
                    <select name="tab" class="form-select">
                        <option value="items" @selected(($activeTab ?? 'items') === 'items')>Items</option>
                        <option value="store-stock" @selected(($activeTab ?? 'items') === 'store-stock')>Store Stock</option>
                        <option value="vendor-return" @selected(($activeTab ?? 'items') === 'vendor-return')>Vendor Return</option>
                        <option value="stock-ledger" @selected(($activeTab ?? 'items') === 'stock-ledger')>Stock Ledger</option>
                        <option value="stock-count" @selected(($activeTab ?? 'items') === 'stock-count')>Stock Count</option>
                    </select>
                </div>
                <div class="col-lg-2 d-flex gap-2">
                    <button class="btn btn-primary w-100" type="submit">Search</button>
                    <a href="{{ route('admin.inventory.index', ['tab' => $activeTab ?? 'items']) }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </div>
            @if(($search ?? '') !== '')
                <div class="small text-muted mt-2">Showing filtered results for <strong>{{ $search }}</strong>.</div>
            @endif
        </div>
    </form>

    <ul class="nav nav-pills inventory-tab-nav mb-3" id="inventory-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ ($activeTab ?? 'items') === 'items' ? 'active' : '' }}" id="items-tab" data-bs-toggle="pill" data-bs-target="#items-pane" type="button" role="tab" aria-controls="items-pane" aria-selected="{{ ($activeTab ?? 'items') === 'items' ? 'true' : 'false' }}">Items</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ ($activeTab ?? 'items') === 'store-stock' ? 'active' : '' }}" id="store-stock-tab" data-bs-toggle="pill" data-bs-target="#store-stock-pane" type="button" role="tab" aria-controls="store-stock-pane" aria-selected="{{ ($activeTab ?? 'items') === 'store-stock' ? 'true' : 'false' }}">Store Stock</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ ($activeTab ?? 'items') === 'vendor-return' ? 'active' : '' }}" id="vendor-return-tab" data-bs-toggle="pill" data-bs-target="#vendor-return-pane" type="button" role="tab" aria-controls="vendor-return-pane" aria-selected="{{ ($activeTab ?? 'items') === 'vendor-return' ? 'true' : 'false' }}">Vendor Return</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ ($activeTab ?? 'items') === 'stock-ledger' ? 'active' : '' }}" id="stock-ledger-tab" data-bs-toggle="pill" data-bs-target="#stock-ledger-pane" type="button" role="tab" aria-controls="stock-ledger-pane" aria-selected="{{ ($activeTab ?? 'items') === 'stock-ledger' ? 'true' : 'false' }}">Stock Ledger</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ ($activeTab ?? 'items') === 'stock-count' ? 'active' : '' }}" id="stock-count-tab" data-bs-toggle="pill" data-bs-target="#stock-count-pane" type="button" role="tab" aria-controls="stock-count-pane" aria-selected="{{ ($activeTab ?? 'items') === 'stock-count' ? 'true' : 'false' }}">Stock Count</button>
        </li>
    </ul>

    <div class="tab-content" id="inventory-tab-content">
        <div class="tab-pane fade {{ ($activeTab ?? 'items') === 'items' ? 'show active' : '' }}" id="items-pane" role="tabpanel" aria-labelledby="items-tab" tabindex="0">
            @php
                $editItemId = (int) request('edit_item', 0);
                $editItem = $editItemId > 0 ? $items->firstWhere('id', $editItemId) : null;
            @endphp

            @if($editItem)
                <div class="card shadow-sm border-primary mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>Edit Item: {{ $editItem->sku }}</strong>
                        <a href="{{ route('admin.inventory.index', ['tab' => 'items']) }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.inventory.items.update', $editItem) }}" class="row g-2">
                            @csrf
                            <div class="col-md-2">
                                <label class="form-label">ItemCode</label>
                                <input type="text" name="item_code" class="form-control" value="{{ old('item_code', $editItem->sku) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">ItemName</label>
                                <input type="text" name="item_name" class="form-control" value="{{ old('item_name', $editItem->name) }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Category</label>
                                <input type="text" name="category" class="form-control" value="{{ old('category', $editItem->category) }}">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">UoM</label>
                                <input type="text" name="uom" class="form-control" value="{{ old('uom', $editItem->uom) }}" required>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Reorder</label>
                                <input type="number" name="reorder_level" class="form-control" value="{{ old('reorder_level', $editItem->reorder_level) }}" min="0" step="0.001">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Status</label>
                                <select name="is_active" class="form-select">
                                    <option value="1" @selected(old('is_active', $editItem->is_active ? '1' : '0') === '1')>Active</option>
                                    <option value="0" @selected(old('is_active', $editItem->is_active ? '1' : '0') === '0')>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button class="btn btn-primary w-100" type="submit">Save</button>
                            </div>
                        </form>
                        <div class="small text-warning mt-2">
                            Note: UoM label change old stock display ko bhi affect kar sakta hai.
                        </div>
                    </div>
                </div>
            @endif

            <div class="card shadow-sm mb-3 inventory-import-shell">
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
                    <div class="card shadow-sm inventory-import-shell">
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
                    <div class="card shadow-sm inventory-items-table">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Items</h5>
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
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $lowIds = collect($lowStockItems ?? [])->pluck('item.id')->all();
                                    @endphp
                                    @forelse($items as $item)
                                        <tr>
                                            <td>{{ $item->id }}</td>
                                            <td>{{ $item->sku }}</td>
                                            <td>{{ $item->name }}</td>
                                            <td>{{ $item->category ?? 'Uncategorized' }}</td>
                                            <td>{{ $item->uom }}</td>
                                            <td>
                                                @php
                                                    $isLow = in_array($item->id, $lowIds, true);
                                                @endphp
                                                <span class="badge {{ $isLow ? 'bg-danger' : 'bg-light text-muted' }}">
                                                    {{ number_format((float) $item->reorder_level, 3) }}
                                                    @if($isLow)
                                                        <span class="ms-1">Low</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td>
                                                @if($item->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.inventory.index', ['tab' => 'items', 'edit_item' => $item->id]) }}#items-pane">Edit</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">{{ ($search ?? '') !== '' ? 'No items matched your search.' : 'No items found' }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade {{ ($activeTab ?? 'items') === 'store-stock' ? 'show active' : '' }}" id="store-stock-pane" role="tabpanel" aria-labelledby="store-stock-tab" tabindex="0">
            <div class="row g-3">
                <div class="col-12">
                    <div class="card shadow-sm inventory-manual-txn">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Store Stock</h5>
                            <span class="text-muted small">Usable stock based on auditable transaction balance.</span>
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>ItemCode</th>
                                        <th>ItemName</th>
                                        <th>Category</th>
                                        <th>UoM</th>
                                        <th>Available Stock</th>
                                        <th>Received Qty</th>
                                        <th>Issued Qty</th>
                                        <th>Latest Movement</th>
                                        <th>Trail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($storeStockRows as $row)
                                        @php
                                            $item = $row['item'];
                                            $latest = $row['latest_movement'];
                                        @endphp
                                        <tr>
                                            <td>{{ $item->sku }}</td>
                                            <td>{{ $item->name }}</td>
                                            <td>{{ $item->category ?? 'Uncategorized' }}</td>
                                            <td>{{ $item->uom }}</td>
                                            <td>
                                                <span class="badge {{ (float) $row['balance'] > 0 ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ number_format((float) $row['balance'], 3) }} {{ $item->uom }}
                                                </span>
                                            </td>
                                            <td>{{ number_format((float) $row['received_qty'], 3) }} {{ $item->uom }}</td>
                                            <td>{{ number_format((float) $row['issued_qty'], 3) }} {{ $item->uom }}</td>
                                            <td>
                                                @if($latest)
                                                    <div>{{ $latest->txn_type }}</div>
                                                    <div class="small text-muted">{{ optional($latest->txn_at)->format('Y-m-d H:i') }}</div>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.inventory.items.trail', $item) }}">Trail</a></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">{{ ($search ?? '') !== '' ? 'No store stock rows matched your search.' : 'No stock rows found' }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                @if(!empty($lowStockItems))
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h5 class="mb-0">Low Stock Items</h5>
                            </div>
                            <div class="card-body table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>ItemCode</th>
                                            <th>ItemName</th>
                                            <th>Balance</th>
                                            <th>Reorder Level</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($lowStockItems as $row)
                                            @php
                                                $item = $row['item'];
                                            @endphp
                                            <tr>
                                                <td>{{ $item->sku }}</td>
                                                <td>{{ $item->name }}</td>
                                                <td>{{ number_format((float) $row['balance'], 3) }} {{ $item->uom }}</td>
                                                <td>{{ number_format((float) $item->reorder_level, 3) }} {{ $item->uom }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Manual Inventory Transaction</h5>
                            <span class="text-muted small">Use for opening balance, adjustments and ad-hoc IN/OUT</span>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.inventory.txns.store') }}" class="row g-2">
                                @csrf
                                <div class="col-md-3">
                                    <label class="form-label">Item</label>
                                    <select name="item_id" id="inv-item-select" class="form-select" required>
                                        <option value="">Select item</option>
                                        @foreach($items as $item)
                                            <option value="{{ $item->id }}">{{ $item->sku }} — {{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="small mt-1" id="inv-balance-indicator"></div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Txn Type</label>
                                    <select name="txn_type" class="form-select" required>
                                        <option value="OPENING">OPENING</option>
                                        <option value="IN">IN</option>
                                        <option value="OUT">OUT</option>
                                        <option value="ADJUSTMENT">ADJUSTMENT</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="txn_at" class="form-control" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" step="0.001" min="0.001" name="quantity" id="inv-qty-input" class="form-control" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Unit</label>
                                    <select name="unit_code" id="inv-unit-select" class="form-select">
                                        <option value="">Base unit</option>
                                    </select>
                                    <div class="small text-muted" id="inv-conversion-preview"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Unit Cost</label>
                                    <input type="number" step="0.01" min="0" name="unit_cost" class="form-control" placeholder="optional for OUT/ADJ">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Remarks</label>
                                    <input type="text" name="remarks" class="form-control" placeholder="optional reference">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button class="btn btn-primary w-100">Post Transaction</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Stock Ledger</h5>
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Item</th>
                                        <th>Txn Type</th>
                                        <th>Qty</th>
                                        <th>Unit Cost</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($ledger as $txn)
                                        @php
                                            $item = $items->firstWhere('id', $txn->item_id);
                                        @endphp
                                        <tr>
                                            <td>{{ optional($txn->txn_at)->format('Y-m-d H:i') }}</td>
                                            <td>{{ $item?->sku }} {{ $item?->name ? '— '.$item->name : '' }}</td>
                                            <td>{{ $txn->txn_type }}</td>
                                            <td>{{ number_format((float) $txn->quantity, 3) }}</td>
                                            <td>{{ number_format((float) $txn->unit_cost, 2) }}</td>
                                            <td>{{ $txn->remarks }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No ledger rows found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade {{ ($activeTab ?? 'items') === 'vendor-return' ? 'show active' : '' }}" id="vendor-return-pane" role="tabpanel" aria-labelledby="vendor-return-tab" tabindex="0">
            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="card shadow-sm inventory-vendor-return">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Vendor Return</h5>
                            <span class="text-muted small">Store stock only</span>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.inventory.vendor-returns.store') }}" class="row g-2">
                                @csrf

                                <div class="col-md-4">
                                    <label class="form-label">Filter by GRN Date</label>
                                    <input type="date" id="vendor-return-source-date-filter" class="form-control">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Search GRN / Vendor / Item</label>
                                    <input type="text" id="vendor-return-source-search" class="form-control" placeholder="Type item, GRN no, vendor, code">
                                </div>

                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" id="vendor-return-source-clear-filter" class="btn btn-outline-secondary w-100 text-nowrap">Clear</button>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Received Stock Source</label>
                                    <select name="goods_receipt_line_id" id="vendor-return-source" class="form-select" required>
                                        <option value="">Select GRN source</option>
                                        @foreach($vendorReturnSources as $source)
                                            <option
                                                value="{{ $source['goods_receipt_line_id'] }}"
                                                data-grn-id="{{ $source['goods_receipt_id'] }}"
                                                data-source-date="{{ \Illuminate\Support\Carbon::parse($source['received_date'])->format('Y-m-d') }}"
                                                data-search-text="{{ strtolower($source['grn_number'].' '.$source['vendor_name'].' '.$source['item_sku'].' '.$source['item_name']) }}"
                                                {{ (string) old('goods_receipt_line_id') === (string)($source['goods_receipt_line_id'] ?? '') ? 'selected' : '' }}>
                                                {{ \Illuminate\Support\Carbon::parse($source['received_date'])->format('Y-m-d') }} — {{ $source['grn_number'] }} — {{ $source['vendor_name'] }} — {{ $source['item_sku'] }} / {{ $source['item_name'] }} — Returnable: {{ number_format((float) $source['returnable_qty'], 3) }} {{ $source['uom'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Only sources with current store-returnable quantity are shown.</small>
                                </div>

                                <div class="col-12 small text-muted" id="vendor-return-source-meta"></div>

                                <div class="col-md-4">
                                    <label class="form-label">Return Date</label>
                                    <input type="date" name="return_date" class="form-control" value="{{ old('return_date', now()->toDateString()) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" step="0.001" min="0.001" name="quantity" id="vendor-return-qty" class="form-control" value="{{ old('quantity') }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Unit</label>
                                    <select name="unit_code" id="vendor-return-unit" class="form-select">
                                        <option value="">Base unit</option>
                                    </select>
                                    <div class="small text-muted" id="vendor-return-conversion"></div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Remarks</label>
                                    <input type="text" name="remarks" class="form-control" value="{{ old('remarks') }}" placeholder="Reason / vendor note">
                                </div>

                                <div class="col-12">
                                    <button class="btn btn-warning" type="submit">Post Vendor Return</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Vendor Returns</h5>
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Return No</th>
                                        <th>Vendor</th>
                                        <th>Source GRN</th>
                                        <th>Item</th>
                                        <th>Qty</th>
                                        <th>Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($vendorReturns as $vendorReturn)
                                        <tr>
                                            <td>{{ \Illuminate\Support\Carbon::parse($vendorReturn->return_date)->format('Y-m-d') }}</td>
                                            <td>{{ $vendorReturn->return_number }}</td>
                                            <td>{{ $vendorReturn->vendor?->name }}</td>
                                            <td>{{ $vendorReturn->goodsReceipt?->grn_number }}</td>
                                            <td>{{ $vendorReturn->item?->sku }} {{ $vendorReturn->item?->name ? '— '.$vendorReturn->item?->name : '' }}</td>
                                            <td>
                                                {{ number_format((float) $vendorReturn->qty_returned, 3) }} {{ $vendorReturn->item?->uom }}
                                                @if($vendorReturn->trans_unit_code && $vendorReturn->trans_quantity)
                                                    <div class="small text-muted">{{ number_format((float) $vendorReturn->trans_quantity, 3) }} {{ $vendorReturn->trans_unit_code }}</div>
                                                @endif
                                            </td>
                                            <td>{{ number_format((float) $vendorReturn->unit_cost, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">{{ ($search ?? '') !== '' ? 'No vendor return rows matched your search.' : 'No vendor returns posted yet' }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade {{ ($activeTab ?? 'items') === 'stock-ledger' ? 'show active' : '' }}" id="stock-ledger-pane" role="tabpanel" aria-labelledby="stock-ledger-tab" tabindex="0">
            <div class="card shadow-sm mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Stock Ledger</h5>
                    <a href="{{ route('admin.inventory.stock-ledger.export', ['tab' => 'stock-ledger', 'q' => $search ?? '', 'from_date' => $stockLedgerFromDate ?? '', 'to_date' => $stockLedgerToDate ?? '', 'item_id' => $stockLedgerItemId ?? '', 'category' => $stockLedgerCategory ?? '', 'txn_type' => $stockLedgerTxnType ?? '', 'reference_type' => $stockLedgerReferenceType ?? '', 'remarks' => request('remarks', '')]) }}" class="btn btn-sm btn-outline-primary">Download CSV</a>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.inventory.index') }}" class="row g-2 align-items-end mb-3">
                        <input type="hidden" name="tab" value="stock-ledger">
                        <div class="col-md-2"><label class="form-label">From</label><input type="date" name="from_date" class="form-control" value="{{ $stockLedgerFromDate ?? '' }}"></div>
                        <div class="col-md-2"><label class="form-label">To</label><input type="date" name="to_date" class="form-control" value="{{ $stockLedgerToDate ?? '' }}"></div>
                        <div class="col-md-2"><label class="form-label">Item</label><select name="item_id" class="form-select"><option value="">All</option>@foreach($items as $item)<option value="{{ $item->id }}" @selected((string)($stockLedgerItemId ?? '') === (string)$item->id)>{{ $item->sku }} - {{ $item->name }}</option>@endforeach</select></div>
                        <div class="col-md-2"><label class="form-label">Category</label><input type="text" name="category" class="form-control" value="{{ $stockLedgerCategory ?? '' }}"></div>
                        <div class="col-md-2"><label class="form-label">Txn Type</label><select name="txn_type" class="form-select"><option value="">All</option>@foreach(($stockLedgerTxnTypes ?? []) as $type)<option value="{{ $type }}" @selected(($stockLedgerTxnType ?? '') === $type)>{{ $type }}</option>@endforeach</select></div>
                        <div class="col-md-2"><label class="form-label">Reference Type</label><select name="reference_type" class="form-select"><option value="">All</option>@foreach(($stockLedgerReferenceTypes ?? []) as $type)<option value="{{ $type }}" @selected(($stockLedgerReferenceType ?? '') === $type)>{{ class_basename($type) }}</option>@endforeach</select></div>
                        <div class="col-md-8"><label class="form-label">Search / Remarks</label><input type="text" name="q" class="form-control" value="{{ $search ?? '' }}" placeholder="item, reference, remarks"></div>
                        <div class="col-md-2 d-grid"><button class="btn btn-primary">Apply</button></div>
                        <div class="col-md-2 d-grid"><a href="{{ route('admin.inventory.index', ['tab' => 'stock-ledger']) }}" class="btn btn-outline-secondary">Clear</a></div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Item Code</th>
                                    <th>Item Name</th>
                                    <th>Type</th>
                                    <th>Qty</th>
                                    <th>UOM</th>
                                    <th>Unit Cost</th>
                                    <th>Reference</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($stockLedgerRows ?? collect()) as $row)
                                    <tr>
                                        <td>{{ \Illuminate\Support\Carbon::parse($row->txn_at)->format('Y-m-d H:i') }}</td>
                                        <td>{{ $row->item_sku }}</td>
                                        <td>{{ $row->item_name }}</td>
                                        <td>{{ $row->txn_type }}</td>
                                        <td>{{ number_format((float) $row->quantity, 3) }}</td>
                                        <td>{{ $row->trans_unit_code ?: $row->item_uom }}</td>
                                        <td>{{ number_format((float) $row->unit_cost, 2) }}</td>
                                        <td>{{ class_basename((string) $row->reference_type) }} @if($row->reference_id)#{{ $row->reference_id }}@endif</td>
                                        <td>{{ $row->remarks }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center text-muted">No stock ledger rows found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade {{ ($activeTab ?? 'items') === 'stock-count' ? 'show active' : '' }}" id="stock-count-pane" role="tabpanel" aria-labelledby="stock-count-tab" tabindex="0">
            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="card shadow-sm">
                        <div class="card-header"><h5 class="mb-0">Create Physical Stock Count</h5></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.inventory.stock-counts.store') }}">
                                @csrf
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label">Count Date</label>
                                        <input type="date" name="count_date" class="form-control" value="{{ old('count_date', now()->toDateString()) }}" required>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Session Remarks</label>
                                        <input type="text" name="remarks" class="form-control" value="{{ old('remarks') }}" placeholder="optional remarks">
                                    </div>
                                </div>
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm table-striped align-middle">
                                        <thead>
                                            <tr>
                                                <th>Item Code</th>
                                                <th>Item Name</th>
                                                <th>System Qty</th>
                                                <th>Counted Qty</th>
                                                <th>Variance</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($items as $item)
                                                @php
                                                    $balanceRow = collect($balances ?? [])->firstWhere('item.id', $item->id) ?? null;
                                                    $systemQty = (float)($balanceRow['balance'] ?? 0);
                                                    $countedQty = (float)old('counted_qty.'.$item->id, $systemQty);
                                                @endphp
                                                <tr>
                                                    <td>{{ $item->sku }}</td>
                                                    <td>{{ $item->name }}</td>
                                                    <td>{{ number_format($systemQty, 3) }} {{ $item->uom }}</td>
                                                    <td><input type="number" step="0.001" min="0" name="counted_qty[{{ $item->id }}]" value="{{ number_format($countedQty, 3, '.', '') }}" class="form-control form-control-sm"></td>
                                                    <td>{{ number_format($countedQty - $systemQty, 3) }}</td>
                                                    <td><input type="text" name="line_remarks[{{ $item->id }}]" value="{{ old('line_remarks.'.$item->id) }}" class="form-control form-control-sm"></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3 d-flex justify-content-end">
                                    <button class="btn btn-primary" type="submit">Create DRAFT Count</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card shadow-sm mb-3">
                        <div class="card-header"><h5 class="mb-0">Count History</h5></div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                @forelse(($stockCountHistory ?? collect()) as $count)
                                    <a href="{{ route('admin.inventory.stock-counts.show', $count) }}" class="list-group-item list-group-item-action {{ optional($selectedStockCount)->id === $count->id ? 'active' : '' }}">
                                        <div class="d-flex justify-content-between">
                                            <strong>#{{ $count->id }} - {{ $count->count_date?->format('Y-m-d') }}</strong>
                                            <span class="badge {{ $count->status === 'POSTED' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $count->status }}</span>
                                        </div>
                                        <div class="small">Created by: {{ optional($count->createdBy)->name ?? 'N/A' }}</div>
                                        @if($count->posted_by)
                                            <div class="small">Posted by: {{ optional($count->postedBy)->name ?? 'N/A' }}</div>
                                        @endif
                                    </a>
                                @empty
                                    <div class="list-group-item text-muted">No stock count sessions yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    @if($selectedStockCount)
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Count Detail #{{ $selectedStockCount->id }}</h5>
                                @if($selectedStockCount->status !== 'POSTED')
                                    <form method="POST" action="{{ route('admin.inventory.stock-counts.post', $selectedStockCount) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success" type="submit">Mark POSTED</button>
                                    </form>
                                @endif
                            </div>
                            <div class="card-body">
                                <div class="small text-muted mb-2">Posting only changes status. No stock transaction or inventory adjustment will be created.</div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>System Qty</th>
                                                <th>Counted Qty</th>
                                                <th>Variance</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($selectedStockCount->lines as $line)
                                                <tr>
                                                    <td>{{ $line->item->sku }} - {{ $line->item->name }}</td>
                                                    <td>{{ number_format((float)$line->system_qty, 3) }} {{ $line->item->uom }}</td>
                                                    <td>{{ number_format((float)$line->counted_qty, 3) }} {{ $line->item->uom }}</td>
                                                    <td>{{ number_format((float)$line->variance_qty, 3) }} {{ $line->item->uom }}</td>
                                                    <td>{{ $line->remarks }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@php
    $oldVendorReturnSourceLineId = old('goods_receipt_line_id');

    $inventoryItemsJson = $items->map(function ($item) use ($balances) {
        $balanceRow = collect($balances ?? [])->firstWhere('item.id', $item->id) ?? null;
        $balance = $balanceRow['balance'] ?? 0;

        return [
            'id' => $item->id,
            'sku' => $item->sku,
            'name' => $item->name,
            'uom' => $item->uom,
            'balance' => (float) $balance,
            'units' => $item->units->map(function ($u) {
                return [
                    'code' => $u->unit_code,
                    'factor' => (float) $u->factor_to_base,
                ];
            })->values()->all(),
        ];
    })->values()->all();

    $vendorReturnSourcesJson = collect($vendorReturnSources ?? [])->map(function ($source) {
        return [
            'goods_receipt_line_id' => $source['goods_receipt_line_id'],
            'goods_receipt_id' => $source['goods_receipt_id'],
            'vendor_id' => $source['vendor_id'],
            'vendor_name' => $source['vendor_name'],
            'grn_number' => $source['grn_number'],
            'item_id' => $source['item_id'],
            'item_name' => $source['item_name'],
            'item_sku' => $source['item_sku'],
            'uom' => $source['uom'],
            'source_received_qty' => (float) $source['source_received_qty'],
            'already_returned_qty' => (float) $source['already_returned_qty'],
            'current_balance_qty' => (float) $source['current_balance_qty'],
            'returnable_qty' => (float) $source['returnable_qty'],
            'units' => $source['units'] ?? [],
        ];
    })->values()->all();
@endphp

@push('scripts')
<script>
    (() => {
        const inventoryTabParamMap = {
            'items-tab': 'items',
            'store-stock-tab': 'store-stock',
            'vendor-return-tab': 'vendor-return',
            'stock-ledger-tab': 'stock-ledger',
            'stock-count-tab': 'stock-count',
        };

        const items = @json($inventoryItemsJson);
        const itemsById = {};
        items.forEach((i) => { itemsById[i.id] = i; });

        const itemSelect = document.getElementById('inv-item-select');
        const unitSelect = document.getElementById('inv-unit-select');
        const qtyInput = document.getElementById('inv-qty-input');
        const balanceIndicator = document.getElementById('inv-balance-indicator');
        const preview = document.getElementById('inv-conversion-preview');

        const returnSources = @json($vendorReturnSourcesJson);
        const returnSourcesByGrnId = {};
        const returnSourcesByLineId = {};
        returnSources.forEach((source) => {
            returnSourcesByGrnId[source.goods_receipt_id] = source;
            if (source.goods_receipt_line_id) {
                returnSourcesByLineId[source.goods_receipt_line_id] = source;
            }
        });

        const returnSourceSelect = document.getElementById('vendor-return-source');
        const returnSourceDateFilter = document.getElementById('vendor-return-source-date-filter');
        const returnSourceSearch = document.getElementById('vendor-return-source-search');
        const returnSourceClearFilter = document.getElementById('vendor-return-source-clear-filter');
        const returnUnitSelect = document.getElementById('vendor-return-unit');
        const returnQtyInput = document.getElementById('vendor-return-qty');
        const returnMeta = document.getElementById('vendor-return-source-meta');
        const returnConversion = document.getElementById('vendor-return-conversion');

        const syncBalanceAndUnits = () => {
            const itemId = Number((itemSelect && itemSelect.value) || 0);
            const item = itemsById[itemId];
            if (!item) {
                if (balanceIndicator) balanceIndicator.textContent = '';
                if (unitSelect) unitSelect.innerHTML = '<option value="">Base unit</option>';
                if (preview) preview.textContent = '';
                return;
            }

            if (balanceIndicator) {
                const balance = item.balance || 0;
                let color = 'text-success';
                if (balance <= 0) {
                    color = 'text-danger';
                }
                balanceIndicator.className = 'small ' + color;
                balanceIndicator.textContent = `Current balance: ${balance.toFixed(3)} ${item.uom}`;
            }

            if (!unitSelect) return;
            const units = item.units || [];
            unitSelect.innerHTML = '';
            const baseOpt = document.createElement('option');
            baseOpt.value = '';
            baseOpt.textContent = item.uom ? `Base unit (${item.uom})` : 'Base unit';
            unitSelect.appendChild(baseOpt);

            units.forEach((u) => {
                const opt = document.createElement('option');
                opt.value = u.code;
                opt.textContent = `${u.code} (x${u.factor.toFixed(3)} ${item.uom})`;
                unitSelect.appendChild(opt);
            });

            syncPreview();
        };

        const syncPreview = () => {
            if (!preview) return;
            const itemId = Number((itemSelect && itemSelect.value) || 0);
            const item = itemsById[itemId];
            const qty = Number((qtyInput && qtyInput.value) || 0);
            const unitCode = (unitSelect && unitSelect.value) || '';

            if (!item || !qty || !unitCode) {
                preview.textContent = '';
                return;
            }

            const unit = (item.units || []).find(function (u) { return u.code === unitCode; });
            if (!unit) {
                preview.textContent = '';
                return;
            }

            const baseQty = qty * unit.factor;
            preview.textContent = `${qty.toFixed(3)} ${unit.code} = ${baseQty.toFixed(3)} ${item.uom}`;
        };

        const syncVendorReturnSource = () => {
            const grnId = Number((returnSourceSelect && returnSourceSelect.value) || 0);
            const source = returnSourcesByLineId[grnId];

            if (!source) {
                if (returnUnitSelect) returnUnitSelect.innerHTML = '<option value="">Base unit</option>';
                if (returnMeta) returnMeta.textContent = '';
                if (returnConversion) returnConversion.textContent = '';
                return;
            }

            if (returnMeta) {
                returnMeta.textContent = `${source.vendor_name} | ${source.item_sku} - ${source.item_name} | Store balance ${source.current_balance_qty.toFixed(3)} ${source.uom} | Source pending ${source.returnable_qty.toFixed(3)} ${source.uom}`;
            }

            if (returnUnitSelect) {
                returnUnitSelect.innerHTML = '';
                const baseOpt = document.createElement('option');
                baseOpt.value = '';
                baseOpt.textContent = source.uom ? `Base unit (${source.uom})` : 'Base unit';
                returnUnitSelect.appendChild(baseOpt);

                (source.units || []).forEach((u) => {
                    const opt = document.createElement('option');
                    opt.value = u.code;
                    opt.textContent = `${u.code} (x${Number(u.factor).toFixed(3)} ${source.uom})`;
                    returnUnitSelect.appendChild(opt);
                });
            }

            syncVendorReturnPreview();
        };

        const syncVendorReturnPreview = () => {
            if (!returnConversion) return;
            const grnId = Number((returnSourceSelect && returnSourceSelect.value) || 0);
            const source = returnSourcesByLineId[grnId];
            const qty = Number((returnQtyInput && returnQtyInput.value) || 0);
            const unitCode = (returnUnitSelect && returnUnitSelect.value) || '';

            if (!source || !qty || !unitCode) {
                returnConversion.textContent = '';
                return;
            }

            const unit = (source.units || []).find(function (u) { return u.code === unitCode; });
            if (!unit) {
                returnConversion.textContent = '';
                return;
            }

            const baseQty = qty * Number(unit.factor);
            returnConversion.textContent = `${qty.toFixed(3)} ${unit.code} = ${baseQty.toFixed(3)} ${source.uom}`;
        };

        const renderVendorReturnSources = () => {
            if (!returnSourceSelect) return;

            const dateValue = (returnSourceDateFilter && returnSourceDateFilter.value) || '';
            const searchValue = ((returnSourceSearch && returnSourceSearch.value) || '').toLowerCase().trim();
            const currentValue = returnSourceSelect.value;

            returnSourceSelect.innerHTML = '<option value="">Select GRN source</option>';

            returnSources.forEach((source) => {
                const sourceDate = String(source.received_date || '').substring(0, 10);
                const haystack = [
                    source.grn_number,
                    source.vendor_name,
                    source.item_sku,
                    source.item_name,
                    source.uom
                ].join(' ').toLowerCase();

                const dateOk = !dateValue || sourceDate === dateValue;
                const searchOk = !searchValue || haystack.includes(searchValue);

                if (!dateOk || !searchOk) return;

                const option = document.createElement('option');
                option.value = source.goods_receipt_line_id || source.goods_receipt_id;
                option.dataset.grnId = source.goods_receipt_id;
                option.dataset.sourceDate = sourceDate;
                option.dataset.searchText = haystack;
                option.textContent = `${sourceDate} — ${source.grn_number} — ${source.vendor_name} — ${source.item_sku} / ${source.item_name} — Returnable: ${Number(source.returnable_qty || 0).toFixed(3)} ${source.uom || ''}`;

                returnSourceSelect.appendChild(option);
            });

            if (currentValue && [...returnSourceSelect.options].some((option) => option.value === currentValue)) {
                returnSourceSelect.value = currentValue;
            } else {
                returnSourceSelect.value = '';
                syncVendorReturnSource();
            }
        };

        const filterVendorReturnSources = renderVendorReturnSources;

        if (itemSelect) itemSelect.addEventListener('change', syncBalanceAndUnits);
        if (unitSelect) unitSelect.addEventListener('change', syncPreview);
        if (qtyInput) qtyInput.addEventListener('input', syncPreview);
        if (returnSourceSelect) returnSourceSelect.addEventListener('change', syncVendorReturnSource);
        if (returnSourceDateFilter) returnSourceDateFilter.addEventListener('change', renderVendorReturnSources);
        if (returnSourceSearch) returnSourceSearch.addEventListener('input', renderVendorReturnSources);
        if (returnSourceClearFilter) returnSourceClearFilter.addEventListener('click', () => {
            if (returnSourceDateFilter) returnSourceDateFilter.value = '';
            if (returnSourceSearch) returnSourceSearch.value = '';
            renderVendorReturnSources();
        });
        if (returnUnitSelect) returnUnitSelect.addEventListener('change', syncVendorReturnPreview);
        if (returnQtyInput) returnQtyInput.addEventListener('input', syncVendorReturnPreview);

        document.querySelectorAll('#inventory-tabs [data-bs-toggle="pill"]').forEach((tabButton) => {
            tabButton.addEventListener('shown.bs.tab', (event) => {
                const tabValue = inventoryTabParamMap[event.target.id] || 'items';
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabValue);
                window.history.replaceState({}, '', url.toString());
            });
        });

        syncBalanceAndUnits();
        renderVendorReturnSources();
        @if($oldVendorReturnSourceLineId)
        if (returnSourceSelect) {
            returnSourceSelect.value = @json((string) $oldVendorReturnSourceLineId);
        }
        @endif
        syncVendorReturnSource();
    })();
</script>
@endpush
