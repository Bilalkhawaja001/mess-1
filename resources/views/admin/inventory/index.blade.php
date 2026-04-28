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
    </ul>

    <div class="tab-content" id="inventory-tab-content">
        <div class="tab-pane fade {{ ($activeTab ?? 'items') === 'items' ? 'show active' : '' }}" id="items-pane" role="tabpanel" aria-labelledby="items-tab" tabindex="0">
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
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">{{ ($search ?? '') !== '' ? 'No items matched your search.' : 'No items found' }}</td>
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
                                <input type="hidden" name="vendor_id" id="vendor-return-vendor-id" value="{{ old('vendor_id') }}">
                                <input type="hidden" name="item_id" id="vendor-return-item-id" value="{{ old('item_id') }}">

                                <div class="col-12">
                                    <label class="form-label">Received Stock Source</label>
                                    <select name="goods_receipt_id" id="vendor-return-source" class="form-select" required>
                                        <option value="">Select GRN source</option>
                                        @foreach($vendorReturnSources as $source)
                                            <option value="{{ $source['goods_receipt_id'] }}" {{ (string) old('goods_receipt_id') === (string) $source['goods_receipt_id'] ? 'selected' : '' }}>
                                                {{ $source['grn_number'] }} — {{ $source['vendor_name'] }} — {{ $source['item_sku'] }} / {{ $source['item_name'] }}
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
    </div>
</div>
@endsection

@php
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
        returnSources.forEach((source) => { returnSourcesByGrnId[source.goods_receipt_id] = source; });

        const returnSourceSelect = document.getElementById('vendor-return-source');
        const returnVendorInput = document.getElementById('vendor-return-vendor-id');
        const returnItemInput = document.getElementById('vendor-return-item-id');
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
            const source = returnSourcesByGrnId[grnId];

            if (!source) {
                if (returnVendorInput) returnVendorInput.value = '';
                if (returnItemInput) returnItemInput.value = '';
                if (returnUnitSelect) returnUnitSelect.innerHTML = '<option value="">Base unit</option>';
                if (returnMeta) returnMeta.textContent = '';
                if (returnConversion) returnConversion.textContent = '';
                return;
            }

            if (returnVendorInput) returnVendorInput.value = source.vendor_id;
            if (returnItemInput) returnItemInput.value = source.item_id;

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
            const source = returnSourcesByGrnId[grnId];
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

        if (itemSelect) itemSelect.addEventListener('change', syncBalanceAndUnits);
        if (unitSelect) unitSelect.addEventListener('change', syncPreview);
        if (qtyInput) qtyInput.addEventListener('input', syncPreview);
        if (returnSourceSelect) returnSourceSelect.addEventListener('change', syncVendorReturnSource);
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
        syncVendorReturnSource();
    })();
</script>
@endpush
