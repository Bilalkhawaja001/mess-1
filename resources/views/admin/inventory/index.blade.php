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
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-danger">{{ count($lowStockItems ?? []) }} low stock</span>
                    <span class="badge bg-secondary">{{ $items->count() }} items</span>
                </div>
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
                                <td colspan="7" class="text-center text-muted">No items found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if(!empty($lowStockItems))
        <div class="col-12 mt-3">
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
                                @php($item = $row['item'])
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

    <div class="col-12 mt-3">
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
@endphp

@push('scripts')
<script>
    (() => {
        const items = @json($inventoryItemsJson);
        const itemsById = {};
        items.forEach((i) => { itemsById[i.id] = i; });

        const itemSelect = document.getElementById('inv-item-select');
        const unitSelect = document.getElementById('inv-unit-select');
        const qtyInput = document.getElementById('inv-qty-input');
        const balanceIndicator = document.getElementById('inv-balance-indicator');
        const preview = document.getElementById('inv-conversion-preview');

        const syncBalanceAndUnits = () => {
            const itemId = Number(itemSelect?.value || 0);
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
            const itemId = Number(itemSelect?.value || 0);
            const item = itemsById[itemId];
            const qty = Number(qtyInput?.value || 0);
            const unitCode = unitSelect?.value || '';

            if (!item || !qty || !unitCode) {
                preview.textContent = '';
                return;
            }

            const unit = (item.units || []).find(u => u.code === unitCode);
            if (!unit) {
                preview.textContent = '';
                return;
            }

            const baseQty = qty * unit.factor;
            preview.textContent = `${qty.toFixed(3)} ${unit.code} = ${baseQty.toFixed(3)} ${item.uom}`;
        };

        itemSelect?.addEventListener('change', syncBalanceAndUnits);
        unitSelect?.addEventListener('change', syncPreview);
        qtyInput?.addEventListener('input', syncPreview);

        syncBalanceAndUnits();
    })();
</script>
@endpush
