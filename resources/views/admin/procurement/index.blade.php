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

    .po-lines {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .po-line-card {
        border: 1px solid rgba(148,163,184,0.16);
        border-radius: 16px;
        padding: 14px;
        background: rgba(248,250,252,0.72);
    }

    .po-line-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        font-size: 0.85rem;
        font-weight: 700;
        color: #334155;
    }

    .procurement-line-grid {
        display: grid;
        grid-template-columns: 1.7fr 0.7fr 0.7fr auto;
        gap: 12px;
        align-items: end;
    }

    .procurement-mini-result {
        font-size: 0.8rem;
        color: #64748b;
        margin-top: 6px;
    }

    .po-summary-list {
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 220px;
    }

    .po-summary-list span {
        display: block;
        color: #64748b;
        font-size: 0.8rem;
    }

    @media (max-width: 991.98px) {
        .procurement-line-grid {
            grid-template-columns: 1fr;
        }
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
        <form method="POST" action="{{ route('admin.procurement.po.store') }}" class="row g-2" id="po-form">@csrf
            <div class="col-12"><select name="vendor_id" class="form-select" required>@foreach($vendors as $v)<option value="{{ $v->id }}">{{ $v->name }}</option>@endforeach</select></div>
            <div class="col-12">
                <div class="po-lines" id="po-lines"></div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-po-line">Add Line</button>
            </div>
            <div class="col-6"><input type="date" name="po_date" class="form-control" required></div>
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
                        <option value="{{ $po->id }}"
                                data-lines='@json($po->lines->map(fn($line) => [
                                    "id" => $line->id,
                                    "item_id" => $line->item_id,
                                    "item_label" => trim(($line->item?->sku ? $line->item->sku." — " : "").($line->item?->name ?? "Unknown Item").($line->item?->uom ? " (".$line->item->uom.")" : "")),
                                    "ordered" => number_format((float) $line->qty_ordered, 3, ".", ""),
                                    "received" => number_format((float) ($line->received_qty ?? 0), 3, ".", ""),
                                    "pending" => number_format((float) ($line->pending_qty ?? 0), 3, ".", ""),
                                ]))'>
                            {{ $po->po_number }} — {{ $po->vendor->name ?? 'Vendor' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">PO Line / Item</label>
                <select id="grn-line-select" class="form-select" required>
                    <option value="">Select PO first</option>
                </select>
                <input type="hidden" name="purchase_order_line_id" id="grn-line-id" required>
                <input type="text" id="grn-item-display" class="form-control mt-2" readonly placeholder="Select PO line first">
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

    <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header">Purchase Orders</div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>PO Number</th><th>Date</th><th>Vendor</th><th>Total Lines</th><th>Total Qty</th><th>Total Amount</th><th>Received Qty</th><th>Pending Qty</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        @foreach($pos as $po)
            <tr>
                <td>{{ $po->po_number }}</td>
                <td>{{ $po->po_date }}</td>
                <td>
                    <div>{{ $po->vendor->name ?? '-' }}</div>
                    <div class="po-summary-list">
                        @foreach($po->lines->take(3) as $line)
                            <span>{{ $line->item?->sku }} {{ $line->item?->name ? '— '.$line->item->name : '' }}</span>
                        @endforeach
                    </div>
                </td>
                <td>{{ $po->total_lines }}</td>
                <td>{{ number_format((float) ($po->total_qty ?? 0), 3) }}</td>
                <td>{{ number_format((float) ($po->total_amount ?? 0), 2) }}</td>
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

        const poLinesWrap = document.getElementById('po-lines');
        const addPoLineBtn = document.getElementById('add-po-line');
        let poLineIndex = 0;

        const resolveItem = (raw) => {
            const needle = raw.trim().toLowerCase();
            return items.find(item => item.label.toLowerCase() === needle || item.search.includes(needle));
        };

        const refreshDuplicateState = () => {
            const hiddenInputs = [...document.querySelectorAll('.po-line-item-id')];
            const values = hiddenInputs.map(i => i.value).filter(Boolean);
            const duplicates = new Set(values.filter((v, idx) => values.indexOf(v) !== idx));
            hiddenInputs.forEach((input) => {
                const note = input.closest('.po-line-card')?.querySelector('.procurement-mini-result');
                if (input.value && duplicates.has(input.value)) {
                    note.textContent = 'Same item cannot be added twice in the same PO.';
                    note.style.color = '#dc2626';
                } else if (input.value) {
                    note.style.color = '#64748b';
                }
            });
        };

        const createPoLine = () => {
            const line = document.createElement('div');
            line.className = 'po-line-card';
            line.dataset.index = poLineIndex;
            line.innerHTML = `
                <div class="po-line-head">
                    <span>PO Line ${poLineIndex + 1}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-po-line">Remove</button>
                </div>
                <div class="procurement-line-grid">
                    <div>
                        <label class="form-label">Search Item</label>
                        <input type="text" class="form-control po-line-search" list="procurement-items-list" placeholder="Search by item code, item name, category" autocomplete="off" required>
                        <input type="hidden" name="lines[${poLineIndex}][item_id]" class="po-line-item-id" required>
                        <div class="procurement-mini-result">Pick an item from the searchable list.</div>
                    </div>
                    <div>
                        <label class="form-label">Qty</label>
                        <input type="number" step="0.001" min="0.001" name="lines[${poLineIndex}][qty_ordered]" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Unit Price</label>
                        <input type="number" step="0.01" min="0" name="lines[${poLineIndex}][unit_price]" class="form-control">
                    </div>
                    <div></div>
                </div>
            `;

            const searchInput = line.querySelector('.po-line-search');
            const hiddenInput = line.querySelector('.po-line-item-id');
            const result = line.querySelector('.procurement-mini-result');

            const sync = () => {
                const match = resolveItem(searchInput.value);
                if (match) {
                    hiddenInput.value = match.id;
                    searchInput.value = match.label;
                    result.textContent = match.label;
                    result.style.color = '#64748b';
                } else {
                    hiddenInput.value = '';
                    result.textContent = searchInput.value.trim() ? 'Pick an exact item from the search list.' : 'Pick an item from the searchable list.';
                    result.style.color = '#64748b';
                }
                refreshDuplicateState();
            };

            searchInput.addEventListener('change', sync);
            searchInput.addEventListener('blur', sync);
            line.querySelector('.remove-po-line').addEventListener('click', () => {
                if (document.querySelectorAll('.po-line-card').length > 1) {
                    line.remove();
                    refreshDuplicateState();
                }
            });

            poLinesWrap.appendChild(line);
            poLineIndex++;
        };

        addPoLineBtn?.addEventListener('click', createPoLine);
        createPoLine();

        const poSelect = document.getElementById('grn-po-select');
        const grnLineSelect = document.getElementById('grn-line-select');
        const grnLineId = document.getElementById('grn-line-id');
        const grnItemDisplay = document.getElementById('grn-item-display');
        const grnItemId = document.getElementById('grn-item-id');
        const grnOrdered = document.getElementById('grn-ordered');
        const grnReceived = document.getElementById('grn-received');
        const grnPending = document.getElementById('grn-pending');
        const grnQtyInput = document.getElementById('grn-qty-input');

        const syncPo = () => {
            const option = poSelect?.selectedOptions?.[0];
            if (!option || !option.value) {
                grnLineSelect.innerHTML = '<option value="">Select PO first</option>';
                grnLineId.value = '';
                grnItemDisplay.value = '';
                grnItemId.value = '';
                grnOrdered.textContent = '0.000';
                grnReceived.textContent = '0.000';
                grnPending.textContent = '0.000';
                grnQtyInput.removeAttribute('max');
                return;
            }

            const lines = JSON.parse(option.dataset.lines || '[]').filter(line => Number(line.pending) > 0);
            grnLineSelect.innerHTML = '<option value="">Select PO line</option>';
            lines.forEach((line) => {
                const opt = document.createElement('option');
                opt.value = line.id;
                opt.dataset.itemId = line.item_id;
                opt.dataset.itemLabel = line.item_label;
                opt.dataset.ordered = line.ordered;
                opt.dataset.received = line.received;
                opt.dataset.pending = line.pending;
                opt.textContent = `${line.item_label} | Pending ${line.pending}`;
                grnLineSelect.appendChild(opt);
            });
            syncPoLine();
        };

        const syncPoLine = () => {
            const line = grnLineSelect?.selectedOptions?.[0];
            if (!line || !line.value) {
                grnLineId.value = '';
                grnItemDisplay.value = '';
                grnItemId.value = '';
                grnOrdered.textContent = '0.000';
                grnReceived.textContent = '0.000';
                grnPending.textContent = '0.000';
                grnQtyInput.removeAttribute('max');
                return;
            }

            grnLineId.value = line.value;
            grnItemDisplay.value = line.dataset.itemLabel || '';
            grnItemId.value = line.dataset.itemId || '';
            grnOrdered.textContent = line.dataset.ordered || '0.000';
            grnReceived.textContent = line.dataset.received || '0.000';
            grnPending.textContent = line.dataset.pending || '0.000';
            grnQtyInput.max = line.dataset.pending || '';
        };

        poSelect?.addEventListener('change', syncPo);
        grnLineSelect?.addEventListener('change', syncPoLine);
        syncPo();
    })();
</script>
@endpush
