@extends('layouts.app')
@section('title','Procurement')
@section('page_title','Procurement')

@push('styles')
<style>
    .procurement-search-result {
        color: #64748b;
        font-size: 0.85rem;
        margin-top: 6px;
    }

    .procurement-note {
        font-size: 0.84rem;
        color: #64748b;
    }

    .procurement-kpi {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-top: 12px;
    }

    .procurement-kpi div {
        padding: 10px 12px;
        border-radius: 14px;
        background: rgba(37,99,235,0.06);
        border: 1px solid rgba(37,99,235,0.10);
        font-size: 0.85rem;
    }

    .procurement-kpi strong {
        display: block;
        font-size: 1rem;
        color: #0f172a;
    }
</style>
@endpush

@section('content')
<div class="row g-3">
    <div class="col-lg-4"><div class="card shadow-sm"><div class="card-header">Create Vendor</div><div class="card-body">
        <form method="POST" action="{{ route('admin.procurement.vendors.store') }}" class="row g-2">@csrf
            <div class="col-12"><input name="name" class="form-control" placeholder="Vendor name" required></div>
            <div class="col-12"><button class="btn btn-primary">Create Vendor</button></div>
        </form>
    </div></div></div>

    <div class="col-lg-4"><div class="card shadow-sm"><div class="card-header">Create PO</div><div class="card-body">
        <form method="POST" action="{{ route('admin.procurement.po.store') }}" class="row g-2">@csrf
            <div class="col-12"><select name="vendor_id" class="form-select" required>@foreach($vendors as $v)<option value="{{ $v->id }}">{{ $v->name }}</option>@endforeach</select></div>
            <div class="col-12">
                <label class="form-label">Search Item</label>
                <input type="text" id="po-item-search" class="form-control procurement-item-search" list="procurement-items-list" placeholder="Search by item code, item name, category" autocomplete="off" required>
                <input type="hidden" name="item_id" id="po-item-id" required>
                <div class="procurement-search-result" id="po-item-result">Pick an item from the searchable list.</div>
            </div>
            <div class="col-6"><input type="date" name="po_date" class="form-control" required></div>
            <div class="col-6"><input type="number" step="0.001" min="0.001" name="qty_ordered" class="form-control" placeholder="qty" required></div>
            <div class="col-12"><input type="number" step="0.01" min="0" name="unit_price" class="form-control" placeholder="unit price"></div>
            <div class="col-12"><button class="btn btn-primary">Create PO</button></div>
        </form>
    </div></div></div>

    <div class="col-lg-4"><div class="card shadow-sm"><div class="card-header">Create GRN</div><div class="card-body">
        <form method="POST" action="{{ route('admin.procurement.grn.store') }}" class="row g-2">@csrf
            <div class="col-12">
                <label class="form-label">Purchase Order</label>
                <select name="purchase_order_id" id="grn-po-select" class="form-select" required>
                    <option value="">Select PO</option>
                    @foreach($pos as $po)
                        @php($line = $po->primary_line)
                        <option value="{{ $po->id }}"
                                data-item-id="{{ $line?->item_id }}"
                                data-item-label="{{ trim(($line?->item?->sku ? $line->item->sku.' — ' : '').($line?->item?->name ?? 'Unknown Item').($line?->item?->uom ? ' ('.$line->item->uom.')' : '')) }}"
                                data-ordered="{{ number_format((float) ($line?->qty_ordered ?? 0), 3, '.', '') }}"
                                data-received="{{ number_format((float) ($po->received_qty ?? 0), 3, '.', '') }}"
                                data-pending="{{ number_format((float) ($po->pending_qty ?? 0), 3, '.', '') }}">
                            {{ $po->po_number }} — {{ $po->vendor->name ?? 'Vendor' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">PO Item</label>
                <input type="text" id="grn-item-display" class="form-control" readonly placeholder="Select PO first">
                <input type="hidden" name="item_id" id="grn-item-id" required>
                <div class="procurement-note mt-2">Stock is posted immediately when GRN is created. Approval does not post stock again.</div>
            </div>
            <div class="col-12">
                <div class="procurement-kpi">
                    <div>Ordered Qty<strong id="grn-ordered">0.000</strong></div>
                    <div>Already Received<strong id="grn-received">0.000</strong></div>
                    <div>Pending Qty<strong id="grn-pending">0.000</strong></div>
                </div>
            </div>
            <div class="col-6"><input type="date" name="received_date" class="form-control" required></div>
            <div class="col-6"><input type="number" step="0.001" min="0.001" name="qty_received" id="grn-qty-input" class="form-control" required></div>
            <div class="col-12"><input type="number" step="0.01" min="0" name="unit_cost" class="form-control" placeholder="unit cost"></div>
            <div class="col-12"><button class="btn btn-primary">Create GRN</button></div>
        </form>
    </div></div></div>

    <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header">Purchase Orders</div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>PO Number</th><th>Date</th><th>Vendor</th><th>Item</th><th>Ordered Qty</th><th>Unit Price</th><th>Received Qty</th><th>Pending Qty</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        @foreach($pos as $po)
            @php($line = $po->primary_line)
            <tr>
                <td>{{ $po->po_number }}</td>
                <td>{{ $po->po_date }}</td>
                <td>{{ $po->vendor->name ?? '-' }}</td>
                <td>{{ $line?->item?->sku }} {{ $line?->item?->name ? '— '.$line->item->name : '' }}</td>
                <td>{{ number_format((float) ($line?->qty_ordered ?? 0), 3) }}</td>
                <td>{{ number_format((float) ($line?->unit_price ?? 0), 2) }}</td>
                <td>{{ number_format((float) ($po->received_qty ?? 0), 3) }}</td>
                <td>{{ number_format((float) ($po->pending_qty ?? 0), 3) }}</td>
                <td>{{ $po->status }}</td>
                <td><form method="POST" action="{{ route('admin.procurement.po.approve',$po) }}">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form></td>
            </tr>
        @endforeach
    </tbody></table></div></div></div>

    <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header">GRNs</div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>GRN Number</th><th>Date</th><th>PO Number</th><th>Vendor</th><th>Item</th><th>Qty Received</th><th>Unit Cost</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        @foreach($grns as $grn)
            @php($grnLine = $grn->lines->first())
            <tr>
                <td>{{ $grn->grn_number }}</td>
                <td>{{ $grn->received_date }}</td>
                <td>{{ $grn->purchaseOrder->po_number ?? $grn->purchase_order_id }}</td>
                <td>{{ $grn->purchaseOrder->vendor->name ?? '-' }}</td>
                <td>{{ $grnLine?->item?->sku }} {{ $grnLine?->item?->name ? '— '.$grnLine->item->name : '' }}</td>
                <td>{{ number_format((float) ($grnLine?->qty_received ?? 0), 3) }}</td>
                <td>{{ number_format((float) ($grnLine?->unit_cost ?? 0), 2) }}</td>
                <td>Posted on Create</td>
                <td><form method="POST" action="{{ route('admin.procurement.grn.approve',$grn) }}">@csrf<button class="btn btn-sm btn-outline-success">Acknowledge</button></form></td>
            </tr>
        @endforeach
    </tbody></table></div></div></div>
</div>

<datalist id="procurement-items-list">
    @foreach($items as $i)
        <option value="{{ $i->sku }} — {{ $i->name }}{{ $i->uom ? ' ('.$i->uom.')' : '' }}{{ $i->category ? ' · '.$i->category : '' }}" data-item-id="{{ $i->id }}"></option>
    @endforeach
</datalist>
@endsection

@push('scripts')
<script>
    (() => {
        const items = @json($items->map(fn($i) => [
            'id' => $i->id,
            'label' => trim(($i->sku ? $i->sku.' — ' : '').$i->name.($i->uom ? ' ('.$i->uom.')' : '').($i->category ? ' · '.$i->category : '')),
            'search' => strtolower(trim(($i->sku ?? '').' '.$i->name.' '.($i->category ?? '').' '.($i->uom ?? ''))),
        ]));

        const bindSearch = (inputId, hiddenId, resultId) => {
            const input = document.getElementById(inputId);
            const hidden = document.getElementById(hiddenId);
            const result = document.getElementById(resultId);
            if (!input || !hidden || !result) return;

            const sync = () => {
                const raw = input.value.trim().toLowerCase();
                const match = items.find(item => item.label.toLowerCase() === raw || item.search.includes(raw));
                if (match) {
                    hidden.value = match.id;
                    input.value = match.label;
                    result.textContent = match.label;
                } else {
                    hidden.value = '';
                    result.textContent = raw ? 'Pick an exact item from the search list.' : 'Pick an item from the searchable list.';
                }
            };

            input.addEventListener('change', sync);
            input.addEventListener('blur', sync);
        };

        bindSearch('po-item-search', 'po-item-id', 'po-item-result');

        const poSelect = document.getElementById('grn-po-select');
        const grnItemDisplay = document.getElementById('grn-item-display');
        const grnItemId = document.getElementById('grn-item-id');
        const grnOrdered = document.getElementById('grn-ordered');
        const grnReceived = document.getElementById('grn-received');
        const grnPending = document.getElementById('grn-pending');
        const grnQtyInput = document.getElementById('grn-qty-input');

        const syncPo = () => {
            const option = poSelect?.selectedOptions?.[0];
            if (!option || !option.value) {
                grnItemDisplay.value = '';
                grnItemId.value = '';
                grnOrdered.textContent = '0.000';
                grnReceived.textContent = '0.000';
                grnPending.textContent = '0.000';
                grnQtyInput.removeAttribute('max');
                return;
            }

            grnItemDisplay.value = option.dataset.itemLabel || '';
            grnItemId.value = option.dataset.itemId || '';
            grnOrdered.textContent = option.dataset.ordered || '0.000';
            grnReceived.textContent = option.dataset.received || '0.000';
            grnPending.textContent = option.dataset.pending || '0.000';
            grnQtyInput.max = option.dataset.pending || '';
        };

        poSelect?.addEventListener('change', syncPo);
        syncPo();
    })();
</script>
@endpush
