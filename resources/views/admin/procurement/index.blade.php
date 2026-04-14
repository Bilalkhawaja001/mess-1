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
        padding: 9px 11px;
        border-radius: 14px;
        background: rgba(37,99,235,0.06);
        border: 1px solid rgba(37,99,235,0.10);
        font-size: 0.82rem;
        display: flex;
        justify-content: space-between;
        align-items: baseline;
    }

    .procurement-kpi strong {
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

    .procurement-line-grid .form-label {
        font-size: 0.78rem;
        margin-bottom: 0.15rem;
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

    .bulk-action-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
        flex-wrap: wrap;
    }

    .bulk-action-count {
        font-size: 0.85rem;
        color: #64748b;
    }

    .procurement-primary-card .card-header {
        padding-top: 0.6rem;
        padding-bottom: 0.5rem;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .procurement-primary-card .card-body {
        padding-top: 0.9rem;
        padding-bottom: 0.9rem;
    }

    .procurement-table-card .card-header {
        padding-top: 0.6rem;
        padding-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .procurement-table-card .table {
        font-size: 0.82rem;
    }

    @media (max-width: 991.98px) {
        .procurement-line-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
@php
    $procurementPoLinesJson = $grnEligiblePos->mapWithKeys(function ($po) {
        return [
            $po->id => $po->lines->map(function ($line) {
                return [
                    'id' => $line->id,
                    'item_id' => $line->item_id,
                    'item_label' => trim(($line->item?->sku ? $line->item->sku.' — ' : '').($line->item?->name ?? 'Unknown Item').($line->item?->uom ? ' ('.$line->item->uom.')' : '')),
                    'ordered' => number_format((float) $line->qty_ordered, 3, '.', ''),
                    'received' => number_format((float) ($line->received_qty ?? 0), 3, '.', ''),
                    'pending' => number_format((float) ($line->pending_qty ?? 0), 3, '.', ''),
                    'unit_price' => number_format((float) $line->unit_price, 2, '.', ''),
                ];
            })->values()->all(),
        ];
    })->all();

    $procurementItemsJson = $items->map(function ($i) {
        return [
            'id' => $i->id,
            'label' => trim(($i->sku ? $i->sku.' — ' : '').$i->name.($i->uom ? ' ('.$i->uom.')' : '').($i->category ? ' · '.$i->category : '')),
            'search' => strtolower(trim(($i->sku ?? '').' '.$i->name.' '.($i->category ?? '').' '.($i->uom ?? ''))),
            'base_uom' => $i->uom,
            'units' => $i->units->map(function ($u) {
                return [
                    'code' => $u->unit_code,
                    'factor' => (float) $u->factor_to_base,
                    'is_default_for_grn' => (bool) $u->is_default_for_grn,
                    'is_default_for_kitchen' => (bool) $u->is_default_for_kitchen,
                ];
            })->values()->all(),
        ];
    })->values()->all();
@endphp
<div class="row g-3">
    <div class="col-lg-4"><div class="card shadow-sm procurement-primary-card"><div class="card-header">Create Vendor</div><div class="card-body">
        <form method="POST" action="{{ route('admin.procurement.vendors.store') }}" class="row g-2">@csrf
            <div class="col-12"><input name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Vendor name" required value="{{ old('name') }}"></div>
            @error('name')
                <div class="col-12"><div class="text-danger small">{{ $message }}</div></div>
            @enderror
            <div class="col-12"><button class="btn btn-primary">Create Vendor</button></div>
        </form>
    </div></div></div>

    <div class="col-lg-4"><div class="card shadow-sm procurement-primary-card"><div class="card-header">Create PO</div><div class="card-body">
        <form method="POST" action="{{ route('admin.procurement.po.store') }}" class="row g-2" id="po-form">@csrf
            <div class="col-12"><select name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror" required><option value="">Select vendor</option>@foreach($vendors as $v)<option value="{{ $v->id }}" @selected((string) old('vendor_id') === (string) $v->id)>{{ $v->name }}</option>@endforeach</select></div>
            @error('vendor_id')
                <div class="col-12"><div class="text-danger small">{{ $message }}</div></div>
            @enderror
            <div class="col-12">
                <div class="po-lines" id="po-lines"></div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-po-line">Add Line</button>
            </div>
            <div class="col-6"><input type="date" name="po_date" class="form-control @error('po_date') is-invalid @enderror" required value="{{ old('po_date') }}"></div>
            @error('po_date')
                <div class="col-12"><div class="text-danger small">{{ $message }}</div></div>
            @enderror
            @error('lines')
                <div class="col-12"><div class="text-danger small">{{ $message }}</div></div>
            @enderror
            <div class="col-12"><button class="btn btn-primary" id="po-submit-btn">Create PO</button></div>
        </form>
    </div></div></div>

    <div class="col-lg-4"><div class="card shadow-sm procurement-primary-card"><div class="card-header">Create GRN</div><div class="card-body">
        <form method="POST" action="{{ route('admin.procurement.grn.store') }}" class="row g-2" id="grn-form">@csrf
            <div class="col-12">
                <label class="form-label">Purchase Order</label>
                <select name="purchase_order_id" id="grn-po-select" class="form-select @error('purchase_order_id') is-invalid @enderror" required>
                    <option value="">Select PO</option>
                    @foreach($grnEligiblePos as $po)
                        <option value="{{ $po->id }}" data-lines='@json($procurementPoLinesJson[$po->id] ?? [])' @selected((string) old('purchase_order_id') === (string) $po->id)>
                            {{ $po->po_number }} — {{ $po->vendor->name ?? 'Vendor' }}
                        </option>
                    @endforeach
                </select>
                @error('purchase_order_id')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12">
                <label class="form-label">PO Line / Item</label>
                <select id="grn-line-select" class="form-select @error('purchase_order_line_id') is-invalid @enderror" required>
                    <option value="">Select PO first</option>
                </select>
                @error('purchase_order_line_id')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
                <input type="hidden" name="purchase_order_line_id" id="grn-line-id" required value="{{ old('purchase_order_line_id') }}">
                <input type="text" id="grn-item-display" class="form-control mt-2" readonly placeholder="Select PO line first">
                <input type="hidden" name="item_id" id="grn-item-id" required value="{{ old('item_id') }}">
                @error('item_id')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
                <div class="procurement-note mt-2">Stock is posted immediately when GRN is created. Approval does not post stock again.</div>
                <div class="text-danger small mt-1 d-none" id="grn-block-message">Receive quantity cannot exceed pending quantity.</div>
            </div>
            <div class="col-12">
                <div class="procurement-kpi">
                    <div>Ordered Qty<strong id="grn-ordered">0.000</strong></div>
                    <div>Already Received<strong id="grn-received">0.000</strong></div>
                    <div>Pending Qty<strong id="grn-pending">0.000</strong></div>
                </div>
            </div>
            <div class="col-6"><input type="date" name="received_date" class="form-control @error('received_date') is-invalid @enderror" required value="{{ old('received_date') }}"></div>
            <div class="col-3"><input type="number" step="0.001" min="0.001" name="qty_received" id="grn-qty-input" class="form-control @error('qty_received') is-invalid @enderror" required value="{{ old('qty_received') }}"></div>
            <div class="col-3">
                <select name="unit_code" id="grn-unit-select" class="form-select @error('unit_code') is-invalid @enderror" required>
                    <option value="">Select unit</option>
                </select>
                <div class="procurement-mini-result" id="grn-conversion-preview"></div>
            </div>
            @error('received_date')
                <div class="col-12"><div class="text-danger small">{{ $message }}</div></div>
            @enderror
            @error('qty_received')
                <div class="col-12"><div class="text-danger small">{{ $message }}</div></div>
            @enderror
            @error('unit_code')
                <div class="col-12"><div class="text-danger small">{{ $message }}</div></div>
            @enderror
            <div class="col-12">
                <label class="form-label">PO Unit Cost</label>
                <input type="text" id="grn-po-unit-cost" class="form-control" readonly placeholder="Select PO line first">
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" name="override_po_rate" id="grn-override-rate" @checked(old('override_po_rate'))>
                    <label class="form-check-label" for="grn-override-rate">Override PO rate</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label">Actual GRN Unit Cost</label>
                <input type="number" step="0.01" min="0.01" name="unit_cost" id="grn-unit-cost-input" class="form-control @error('unit_cost') is-invalid @enderror" placeholder="unit cost" required value="{{ old('unit_cost') }}">
            </div>
            @error('unit_cost')
                <div class="col-12"><div class="text-danger small">{{ $message }}</div></div>
            @enderror
            <div class="col-12 d-none" id="grn-override-reason-wrap">
                <label class="form-label">Override Reason</label>
                <input type="text" name="override_reason" id="grn-override-reason" class="form-control @error('override_reason') is-invalid @enderror" maxlength="500" placeholder="Why actual GRN rate differs from PO rate" value="{{ old('override_reason') }}">
            </div>
            @error('override_reason')
                <div class="col-12"><div class="text-danger small">{{ $message }}</div></div>
            @enderror
            <div class="col-12"><button class="btn btn-primary" id="grn-submit-btn">Create GRN</button></div>
        </form>
    </div></div></div>

    <div class="col-lg-6"><div class="card shadow-sm procurement-table-card"><div class="card-header">Purchase Orders</div><div class="card-body table-responsive">
        <form method="POST" action="{{ route('admin.procurement.po.bulk-approve') }}" id="po-bulk-form">
            @csrf
            <div class="bulk-action-bar">
                <div class="bulk-action-count"><span id="po-selected-count">0</span> PO(s) selected</div>
                <button type="submit" class="btn btn-sm btn-outline-success" id="po-bulk-submit" disabled>Bulk Approve</button>
            </div>
            <table class="table table-sm"><thead><tr><th><input type="checkbox" id="po-select-all"></th><th>PO Number</th><th>Date</th><th>Vendor</th><th>Total Lines</th><th>Total Qty</th><th>Total Amount</th><th>Received Qty</th><th>Pending Qty</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                @foreach($pos as $po)
                    @php
                        $poSelectable = $po->status !== 'APPROVED';
                    @endphp
                    <tr>
                        <td>
                            @if($poSelectable)
                                <input type="checkbox" class="po-row-check" name="po_ids[]" value="{{ $po->id }}">
                            @endif
                        </td>
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
                        <td class="text-end">
                            @if($poSelectable)
                                <button type="submit" formaction="{{ route('admin.procurement.po.approve',$po) }}" formmethod="POST" class="btn btn-sm btn-outline-success">Approve</button>
                            @else
                                <span class="text-muted small">Approved</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody></table>
        </form>
    </div></div></div>

    <div class="col-lg-6"><div class="card shadow-sm procurement-table-card"><div class="card-header">GRNs</div><div class="card-body table-responsive">
        <form method="POST" action="{{ route('admin.procurement.grn.bulk-approve') }}" id="grn-bulk-form">
            @csrf
            <div class="bulk-action-bar">
                <div class="bulk-action-count"><span id="grn-selected-count">0</span> GRN(s) selected</div>
                <button type="submit" class="btn btn-sm btn-outline-success" id="grn-bulk-submit" disabled>Bulk Acknowledge</button>
            </div>
            <table class="table table-sm"><thead><tr><th><input type="checkbox" id="grn-select-all"></th><th>GRN Number</th><th>Date</th><th>PO Number</th><th>Vendor</th><th>Item</th><th>Qty Received</th><th>Unit Cost</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                @foreach($grns as $grn)
                    @php
                        $grnLine = $grn->lines->first();
                    @endphp
                    <tr>
                        <td><input type="checkbox" class="grn-row-check" name="grn_ids[]" value="{{ $grn->id }}"></td>
                        <td>{{ $grn->grn_number }}</td>
                        <td>{{ $grn->received_date }}</td>
                        <td>{{ $grn->purchaseOrder->po_number ?? $grn->purchase_order_id }}</td>
                        <td>{{ $grn->purchaseOrder->vendor->name ?? '-' }}</td>
                        <td>{{ $grnLine?->item?->sku }} {{ $grnLine?->item?->name ? '— '.$grnLine->item->name : '' }}</td>
                        <td>{{ number_format((float) ($grnLine?->qty_received ?? 0), 3) }}</td>
                        <td>{{ number_format((float) ($grnLine?->unit_cost ?? 0), 2) }}</td>
                        <td>Posted on Create</td>
                        <td class="text-end"><button type="submit" formaction="{{ route('admin.procurement.grn.approve',$grn) }}" formmethod="POST" class="btn btn-sm btn-outline-success">Acknowledge</button></td>
                    </tr>
                @endforeach
            </tbody></table>
        </form>
    </div></div></div>
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
        const items = @json($procurementItemsJson);
        const unitsByItemId = {};
        const itemsById = {};

        items.forEach((item) => {
            unitsByItemId[item.id] = item.units || [];
            itemsById[item.id] = item;
        });

        const poLinesWrap = document.getElementById('po-lines');
        const addPoLineBtn = document.getElementById('add-po-line');
        const poSubmitBtn = document.getElementById('po-submit-btn');
        const grnSubmitBtn = document.getElementById('grn-submit-btn');
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
                        <input type="number" step="0.01" min="0.01" name="lines[${poLineIndex}][unit_price]" class="form-control" required>
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
        const grnBlockMessage = document.getElementById('grn-block-message');
        const grnUnitSelect = document.getElementById('grn-unit-select');
        const grnConversionPreview = document.getElementById('grn-conversion-preview');
        const grnPoUnitCost = document.getElementById('grn-po-unit-cost');
        const grnUnitCostInput = document.getElementById('grn-unit-cost-input');
        const grnOverrideRate = document.getElementById('grn-override-rate');
        const grnOverrideReasonWrap = document.getElementById('grn-override-reason-wrap');
        const grnOverrideReason = document.getElementById('grn-override-reason');

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
                if (grnPoUnitCost) grnPoUnitCost.value = '';
                if (grnUnitCostInput) grnUnitCostInput.value = '';
                if (grnOverrideRate) grnOverrideRate.checked = false;
                if (grnOverrideReasonWrap) grnOverrideReasonWrap.classList.add('d-none');
                if (grnOverrideReason) grnOverrideReason.value = '';
                if (grnUnitCostInput) grnUnitCostInput.readOnly = true;
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
                opt.dataset.unitPrice = line.unit_price;
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
                if (grnPoUnitCost) grnPoUnitCost.value = '';
                if (grnUnitCostInput) grnUnitCostInput.value = '';
                if (grnOverrideRate) grnOverrideRate.checked = false;
                if (grnOverrideReasonWrap) grnOverrideReasonWrap.classList.add('d-none');
                if (grnOverrideReason) grnOverrideReason.value = '';
                if (grnUnitCostInput) grnUnitCostInput.readOnly = true;
                if (grnUnitSelect) {
                    grnUnitSelect.innerHTML = '<option value="">Select unit</option>';
                }
                if (grnConversionPreview) {
                    grnConversionPreview.textContent = '';
                }
                return;
            }

            grnLineId.value = line.value;
            grnItemDisplay.value = line.dataset.itemLabel || '';
            grnItemId.value = line.dataset.itemId || '';
            grnOrdered.textContent = line.dataset.ordered || '0.000';
            grnReceived.textContent = line.dataset.received || '0.000';
            grnPending.textContent = line.dataset.pending || '0.000';
            grnQtyInput.max = line.dataset.pending || '';
            if (grnPoUnitCost) grnPoUnitCost.value = line.dataset.unitPrice || '';
            if (grnUnitCostInput) grnUnitCostInput.value = line.dataset.unitPrice || '';
            if (grnOverrideRate) grnOverrideRate.checked = false;
            if (grnOverrideReasonWrap) grnOverrideReasonWrap.classList.add('d-none');
            if (grnOverrideReason) grnOverrideReason.value = '';
            if (grnUnitCostInput) grnUnitCostInput.readOnly = true;

            if (grnUnitSelect) {
                const itemId = Number(line.dataset.itemId || 0);
                const units = unitsByItemId[itemId] || [];
                const item = itemsById[itemId];

                grnUnitSelect.innerHTML = '';

                if (units.length === 0) {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = item && item.base_uom ? `Base unit (${item.base_uom})` : 'Base unit';
                    grnUnitSelect.appendChild(opt);
                } else {
                    const defaultUnit = units.find(u => u.is_default_for_grn) || units.find(u => u.factor === 1) || units[0];
                    units.forEach((u) => {
                        const opt = document.createElement('option');
                        opt.value = u.code;
                        opt.textContent = `${u.code} (x${u.factor.toFixed(3)} ${item && item.base_uom ? item.base_uom : ''})`;
                        if (defaultUnit && defaultUnit.code === u.code) {
                            opt.selected = true;
                        }
                        grnUnitSelect.appendChild(opt);
                    });
                }

                syncGrnConversion();
            }
        };

        const syncGrnConversion = () => {
            if (!grnConversionPreview || !grnUnitSelect) return;

            const qty = Number(grnQtyInput.value || 0);
            const line = grnLineSelect?.selectedOptions?.[0];
            if (!line || !line.value || !qty) {
                grnConversionPreview.textContent = '';
                return;
            }

            const itemId = Number(line.dataset.itemId || 0);
            const units = unitsByItemId[itemId] || [];
            const item = itemsById[itemId];
            const selectedCode = grnUnitSelect.value;
            const unit = units.find(u => u.code === selectedCode);

            if (!unit || !item) {
                grnConversionPreview.textContent = '';
                return;
            }

            const baseQty = qty * unit.factor;
            grnConversionPreview.textContent = `${qty.toFixed(3)} ${unit.code} = ${baseQty.toFixed(3)} ${item.base_uom}`;
        };

        const syncGrnRateMode = () => {
            const overrideEnabled = !!grnOverrideRate?.checked;
            if (grnUnitCostInput) {
                grnUnitCostInput.readOnly = !overrideEnabled;
            }
            if (grnOverrideReasonWrap) {
                grnOverrideReasonWrap.classList.toggle('d-none', !overrideEnabled);
            }
            if (grnOverrideReason) {
                grnOverrideReason.disabled = !overrideEnabled;
                if (!overrideEnabled) {
                    grnOverrideReason.value = '';
                }
            }
            if (!overrideEnabled && grnPoUnitCost && grnUnitCostInput) {
                grnUnitCostInput.value = grnPoUnitCost.value || '';
            }
        };

        const syncGrnQtyGuard = () => {
            const pending = Number(grnPending.textContent || 0);
            const qty = Number(grnQtyInput.value || 0);
            const blocked = !grnLineId.value || pending <= 0 || (qty > 0 && qty > pending);

            if (pending <= 0 && grnLineId.value) {
                grnBlockMessage.textContent = 'This PO line is already fully received.';
                grnBlockMessage.classList.remove('d-none');
            } else if (qty > pending && qty > 0) {
                grnBlockMessage.textContent = 'Receive quantity cannot exceed pending quantity.';
                grnBlockMessage.classList.remove('d-none');
            } else {
                grnBlockMessage.classList.add('d-none');
            }

            if (grnSubmitBtn) {
                grnSubmitBtn.disabled = blocked;
            }
        };

        poSelect?.addEventListener('change', syncPo);
        grnLineSelect?.addEventListener('change', () => {
            syncPoLine();
            syncGrnQtyGuard();
        });
        grnQtyInput?.addEventListener('input', () => {
            syncGrnQtyGuard();
            syncGrnConversion();
        });
        grnUnitSelect?.addEventListener('change', syncGrnConversion);
        grnOverrideRate?.addEventListener('change', syncGrnRateMode);

        const bindBulkSelection = ({ selectAllId, rowSelector, countId, submitId, formId }) => {
            const selectAll = document.getElementById(selectAllId);
            const rows = [...document.querySelectorAll(rowSelector)];
            const countNode = document.getElementById(countId);
            const submitBtn = document.getElementById(submitId);
            const form = document.getElementById(formId);

            const sync = () => {
                const selected = rows.filter((row) => row.checked).length;
                if (countNode) countNode.textContent = String(selected);
                if (submitBtn) submitBtn.disabled = selected === 0;
                if (selectAll) {
                    selectAll.checked = rows.length > 0 && selected === rows.length;
                    selectAll.indeterminate = selected > 0 && selected < rows.length;
                    selectAll.disabled = rows.length === 0;
                }
            };

            selectAll?.addEventListener('change', () => {
                rows.forEach((row) => { row.checked = selectAll.checked; });
                sync();
            });

            rows.forEach((row) => row.addEventListener('change', sync));
            form?.addEventListener('submit', () => {
                if (submitBtn) submitBtn.disabled = true;
            });

            sync();
        };

        document.getElementById('po-form')?.addEventListener('submit', () => {
            if (poSubmitBtn) {
                poSubmitBtn.disabled = true;
            }
        });

        document.getElementById('grn-form')?.addEventListener('submit', (event) => {
            syncGrnQtyGuard();
            if (grnSubmitBtn?.disabled) {
                event.preventDefault();
                return;
            }
            grnSubmitBtn.disabled = true;
        });

        bindBulkSelection({
            selectAllId: 'po-select-all',
            rowSelector: '.po-row-check',
            countId: 'po-selected-count',
            submitId: 'po-bulk-submit',
            formId: 'po-bulk-form',
        });

        bindBulkSelection({
            selectAllId: 'grn-select-all',
            rowSelector: '.grn-row-check',
            countId: 'grn-selected-count',
            submitId: 'grn-bulk-submit',
            formId: 'grn-bulk-form',
        });

        syncPo();
        syncGrnRateMode();
        syncGrnQtyGuard();
    })();
</script>
@endpush
