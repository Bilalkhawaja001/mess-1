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
        padding: 0.55rem 0.75rem;
        border-radius: 999px;
        background: rgba(15,23,42,0.02);
        border: 1px solid rgba(148,163,184,0.35);
    }

    .procurement-kpi div {
        padding: 0 10px;
        border-radius: 0;
        background: transparent;
        border: none;
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

    .procurement-primary-card .card-header {
        padding-top: 0.6rem;
        padding-bottom: 0.5rem;
        font-size: 0.92rem;
        font-weight: 700;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(148,163,184,0.32);
    }

    .procurement-primary-card .card-body {
        padding-top: 0.95rem;
        padding-bottom: 1rem;
        background: radial-gradient(circle at top left, rgba(37,99,235,0.06), transparent 55%);
    }

    .procurement-table-card .card-header {
        padding-top: 0.6rem;
        padding-bottom: 0.5rem;
        font-size: 0.9rem;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .procurement-table-card .table {
        font-size: 0.82rem;
    }

    .procurement-section-kicker {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #64748b;
    }

    .procurement-section-title {
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .procurement-section-sub {
        font-size: 0.82rem;
        color: #64748b;
    }

    .bulk-action-bar {
        padding: 0.4rem 0.75rem;
        border-radius: 999px;
        background: rgba(15,23,42,0.02);
        border: 1px dashed rgba(148,163,184,0.45);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
        flex-wrap: wrap;
    }

    .bulk-action-count strong {
        font-weight: 600;
        color: #0f172a;
    }

    .procurement-ops-shell {
        border-radius: 18px;
        padding: 0.85rem 1rem 1rem;
        background: linear-gradient(135deg, rgba(15,23,42,0.02), rgba(37,99,235,0.04));
        border: 1px solid rgba(148,163,184,0.35);
    }

    .procurement-group-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        padding-top: 0.45rem;
        margin-bottom: 0.25rem;
        border-top: 1px dashed rgba(148,163,184,0.5);
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
<div class="row g-3 mb-2">
    <div class="col-12 d-flex justify-content-between align-items-end flex-wrap gap-2">
        <div>
            <div class="procurement-section-kicker">Procurement workspace</div>
            <div class="procurement-section-title">Vendors, Purchase Orders &amp; GRNs</div>
            <div class="procurement-section-sub">Create vendors, raise purchase orders and post GRNs into auditable store stock.</div>
        </div>
    </div>
</div>

<div class="procurement-ops-shell mb-3">
    <div class="row g-3">
        <div class="col-lg-4"><div class="card shadow-sm procurement-primary-card"><div class="card-header"><span>Create Vendor</span><span class="text-muted small">Vendor master</span></div><div class="card-body">
            <form method="POST" action="{{ route('admin.procurement.vendors.store') }}" class="row g-2">@csrf
                <div class="col-12"><input name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Vendor name" required value="{{ old('name') }}"></div>
                @error('name')
                    <div class="col-12"><div class="text-danger small">{{ $message }}</div></div>
                @enderror
                <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary">Create Vendor</button></div>
            </form>
        </div></div></div>

        <div class="col-lg-4"><div class="card shadow-sm procurement-primary-card"><div class="card-header"><span>Create PO</span><span class="text-muted small">Order lines</span></div><div class="card-body">
            <form method="POST" action="{{ route('admin.procurement.po.store') }}" class="row g-2" id="po-form">@csrf
                <div class="col-12">
                    <div class="procurement-group-label">Vendor</div>
                    <select name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror" required><option value="">Select vendor</option>@foreach($vendors as $v)<option value="{{ $v->id }}" @selected((string) old('vendor_id') === (string) $v->id)>{{ $v->name }}</option>@endforeach</select>
                </div>
                @error('vendor_id')
                    <div class="col-12"><div class="text-danger small">{{ $message }}</div></div>
                @enderror
                <div class="col-12">
                    <div class="procurement-group-label">PO lines</div>
                    <div class="po-lines" id="po-lines"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-po-line">Add Line</button>
                </div>
                <div class="col-12">
                    <div class="procurement-group-label">PO date &amp; action</div>
                </div>
                <div class="col-6"><input type="date" name="po_date" class="form-control @error('po_date') is-invalid @enderror" required value="{{ old('po_date') }}"></div>
                @error('po_date')
                    <div class="col-12"><div class="text-danger small">{{ $message }}</div></div>
                @enderror
                @error('lines')
                    <div class="col-12"><div class="text-danger small">{{ $message }}</div></div>
                @enderror
                <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary" id="po-submit-btn">Create PO</button></div>
            </form>
        </div></div></div>

        <div class="col-lg-4"><div class="card shadow-sm procurement-primary-card"><div class="card-header"><span>Create GRN</span><span class="text-muted small">Receive into stock</span></div><div class="card-body">
            <form method="POST" action="{{ route('admin.procurement.grn.store') }}" class="row g-2" id="grn-form">@csrf
                <div class="col-12">
                    <div class="procurement-group-label">Purchase order</div>
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
                    <div class="procurement-group-label">Receive date</div>
                    <input type="date" name="received_date" class="form-control @error('received_date') is-invalid @enderror" required value="{{ old('received_date') }}">
                    @error('received_date')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <div class="procurement-group-label">PO lines to receive</div>
                    <div class="procurement-note mb-2">Tick only the rows received now. Unchecked rows stay pending. Qty defaults to pending and can be edited for partial receive.</div>
                    @error('receive_rows')
                        <div class="text-danger small mb-2">{{ $message }}</div>
                    @enderror
                    <div class="table-responsive grn-line-table">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Receive?</th>
                                    <th>Item</th>
                                    <th>Ordered</th>
                                    <th>Received</th>
                                    <th>Pending</th>
                                    <th>Receive Qty</th>
                                    <th>Unit</th>
                                    <th>Unit Cost</th>
                                    <th>Override</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="grn-lines-body">
                                <tr>
                                    <td colspan="10" class="text-muted text-center">Select PO to load pending lines.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="procurement-note mt-2">Stock is posted immediately when GRN is created. Approval does not post stock again.</div>
                </div>
                <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary" id="grn-submit-btn">Receive Selected Items</button></div>
            </form>
        </div></div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6"><div class="card shadow-sm procurement-table-card"><div class="card-header"><span>Purchase Orders</span><span class="text-muted small">Draft &amp; approved</span></div><div class="card-body table-responsive">
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

    <div class="col-lg-6"><div class="card shadow-sm procurement-table-card"><div class="card-header"><span>GRNs</span><span class="text-muted small">Posted on create</span></div><div class="card-body table-responsive">
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
        const grnLinesBody = document.getElementById('grn-lines-body');

        const makeUnitOptions = (itemId, selectedCode = '') => {
            const units = unitsByItemId[itemId] || [];
            const item = itemsById[itemId];
            if (units.length === 0) {
                return `<option value="">${item && item.base_uom ? `Base unit (${item.base_uom})` : 'Base unit'}</option>`;
            }

            const defaultUnit = units.find(u => u.code === selectedCode) || units.find(u => u.is_default_for_grn) || units.find(u => u.factor === 1) || units[0];
            return units.map((u) => `<option value="${u.code}" ${defaultUnit && defaultUnit.code === u.code ? 'selected' : ''}>${u.code} (x${u.factor.toFixed(3)} ${item && item.base_uom ? item.base_uom : ''})</option>`).join('');
        };

        const renderGrnRows = () => {
            const option = poSelect?.selectedOptions?.[0];
            if (!option || !option.value) {
                grnLinesBody.innerHTML = '<tr><td colspan="10" class="text-muted text-center">Select PO to load pending lines.</td></tr>';
                if (grnSubmitBtn) grnSubmitBtn.disabled = true;
                return;
            }

            const oldRows = @json(old('receive_rows', []));
            const oldRowsByLineId = {};
            (oldRows || []).forEach((row) => {
                if (row && row.purchase_order_line_id) {
                    oldRowsByLineId[String(row.purchase_order_line_id)] = row;
                }
            });

            const lines = JSON.parse(option.dataset.lines || '[]').filter(line => Number(line.pending) > 0);
            if (lines.length === 0) {
                grnLinesBody.innerHTML = '<tr><td colspan="10" class="text-muted text-center">This PO has no pending lines.</td></tr>';
                if (grnSubmitBtn) grnSubmitBtn.disabled = true;
                return;
            }

            grnLinesBody.innerHTML = lines.map((line, index) => {
                const oldRow = oldRowsByLineId[String(line.id)] || {};
                const checked = !!oldRow.selected;
                const qtyValue = oldRow.qty_received ?? line.pending;
                const costValue = oldRow.unit_cost ?? line.unit_price;
                const remarksValue = oldRow.remarks ?? '';
                const overrideChecked = !!oldRow.override_po_rate;
                const overrideReason = oldRow.override_reason ?? '';
                const unitOptions = makeUnitOptions(Number(line.item_id), oldRow.unit_code || '');
                return `
                    <tr data-line-index="${index}" data-pending="${line.pending}" data-item-id="${line.item_id}" data-line-id="${line.id}">
                        <td>
                            <div class="form-check">
                                <input class="form-check-input grn-row-selected" type="checkbox" name="receive_rows[${index}][selected]" value="1" ${checked ? 'checked' : ''}>
                            </div>
                            <input type="hidden" name="receive_rows[${index}][purchase_order_line_id]" value="${line.id}">
                            <input type="hidden" name="receive_rows[${index}][item_id]" value="${line.item_id}">
                        </td>
                        <td>${line.item_label}</td>
                        <td>${line.ordered}</td>
                        <td>${line.received}</td>
                        <td><strong>${line.pending}</strong></td>
                        <td>
                            <input type="number" step="0.001" min="0.001" max="${line.pending}" name="receive_rows[${index}][qty_received]" class="form-control grn-row-qty" value="${qtyValue}">
                            <div class="small text-danger d-none grn-row-error"></div>
                        </td>
                        <td>
                            <select name="receive_rows[${index}][unit_code]" class="form-select grn-row-unit">${unitOptions}</select>
                            <div class="procurement-mini-result grn-row-conversion"></div>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0.01" name="receive_rows[${index}][unit_cost]" class="form-control grn-row-cost" value="${costValue}">
                        </td>
                        <td>
                            <div class="form-check mb-1">
                                <input class="form-check-input grn-row-override" type="checkbox" name="receive_rows[${index}][override_po_rate]" value="1" ${overrideChecked ? 'checked' : ''}>
                                <label class="form-check-label small">Override</label>
                            </div>
                            <input type="text" name="receive_rows[${index}][override_reason]" class="form-control grn-row-override-reason ${overrideChecked ? '' : 'd-none'}" placeholder="reason" value="${overrideReason}">
                        </td>
                        <td>
                            <input type="text" name="receive_rows[${index}][remarks]" class="form-control" placeholder="optional remarks" value="${remarksValue}">
                        </td>
                    </tr>
                `;
            }).join('');

            bindGrnRowEvents();
            syncGrnRowsState();
        };

        const syncSingleGrnRow = (row) => {
            const selected = row.querySelector('.grn-row-selected')?.checked;
            const qtyInput = row.querySelector('.grn-row-qty');
            const unitSelect = row.querySelector('.grn-row-unit');
            const costInput = row.querySelector('.grn-row-cost');
            const overrideInput = row.querySelector('.grn-row-override');
            const overrideReasonInput = row.querySelector('.grn-row-override-reason');
            const errorNode = row.querySelector('.grn-row-error');
            const previewNode = row.querySelector('.grn-row-conversion');
            const pending = Number(row.dataset.pending || 0);
            const itemId = Number(row.dataset.itemId || 0);
            const qty = Number(qtyInput?.value || 0);
            const cost = Number(costInput?.value || 0);
            const selectedCode = unitSelect?.value || '';
            const item = itemsById[itemId];
            const unit = (unitsByItemId[itemId] || []).find(u => u.code === selectedCode);
            const overrideEnabled = !!overrideInput?.checked;

            [qtyInput, unitSelect, costInput, overrideInput].forEach((el) => {
                if (el) el.disabled = !selected;
            });

            if (overrideReasonInput) {
                overrideReasonInput.disabled = !selected || !overrideEnabled;
                overrideReasonInput.classList.toggle('d-none', !selected || !overrideEnabled);
            }

            if (previewNode) {
                if (selected && unit && item && qty > 0) {
                    previewNode.textContent = `${qty.toFixed(3)} ${unit.code} = ${(qty * unit.factor).toFixed(3)} ${item.base_uom}`;
                } else {
                    previewNode.textContent = '';
                }
            }

            if (!selected) {
                errorNode?.classList.add('d-none');
                if (errorNode) errorNode.textContent = '';
                return true;
            }

            let error = '';
            if (!(qty > 0)) {
                error = 'Qty must be greater than zero.';
            } else if (qty > pending) {
                error = 'Qty cannot exceed pending.';
            } else if (!unitSelect?.value) {
                error = 'Unit is required.';
            } else if (!(cost > 0)) {
                error = 'Unit cost must be greater than zero.';
            } else if (overrideEnabled && !(overrideReasonInput?.value || '').trim()) {
                error = 'Override reason is required.';
            }

            if (errorNode) {
                errorNode.textContent = error;
                errorNode.classList.toggle('d-none', error === '');
            }

            return error === '';
        };

        const syncGrnRowsState = () => {
            const rows = [...document.querySelectorAll('#grn-lines-body tr[data-line-index]')];
            const selectedRows = rows.filter((row) => row.querySelector('.grn-row-selected')?.checked);
            const allValid = rows.every((row) => syncSingleGrnRow(row));
            if (grnSubmitBtn) {
                grnSubmitBtn.disabled = selectedRows.length === 0 || !allValid;
            }
        };

        const bindGrnRowEvents = () => {
            document.querySelectorAll('.grn-row-selected, .grn-row-qty, .grn-row-unit, .grn-row-cost, .grn-row-override, .grn-row-override-reason').forEach((el) => {
                el.addEventListener('change', syncGrnRowsState);
                el.addEventListener('input', syncGrnRowsState);
            });
        };

        poSelect?.addEventListener('change', renderGrnRows);

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
            syncGrnRowsState();
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

        renderGrnRows();
    })();
</script>
@endpush
