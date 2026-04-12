@extends('layouts.app')
@section('title','Procurement')
@section('page_title','Procurement')

@push('styles')
<style>
    .procurement-shell {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .procurement-top-grid {
        display: grid;
        grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.35fr) minmax(0, 1.15fr);
        gap: 18px;
        align-items: start;
    }

    .procurement-bottom-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 18px;
    }

    .procurement-card {
        border: 1px solid rgba(148,163,184,0.14);
        border-radius: 18px;
        overflow: hidden;
    }

    .procurement-card .card-header {
        padding: 14px 18px;
        background: linear-gradient(180deg, rgba(248,250,252,0.95) 0%, rgba(255,255,255,1) 100%);
        border-bottom: 1px solid rgba(148,163,184,0.12);
    }

    .procurement-card .card-body {
        padding: 18px;
    }

    .procurement-header-title {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .procurement-header-title strong {
        font-size: 0.98rem;
        color: #0f172a;
    }

    .procurement-header-title span {
        font-size: 0.79rem;
        color: #64748b;
    }

    .procurement-section-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .procurement-note {
        font-size: 0.79rem;
        color: #64748b;
        line-height: 1.45;
    }

    .procurement-form-grid {
        display: grid;
        gap: 14px;
    }

    .procurement-action-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .procurement-action-row .btn {
        min-width: 124px;
    }

    .po-lines {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .po-line-card {
        border: 1px solid rgba(148,163,184,0.16);
        border-radius: 18px;
        padding: 16px;
        background: linear-gradient(180deg, rgba(248,250,252,0.92) 0%, rgba(255,255,255,0.98) 100%);
    }

    .po-line-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        gap: 10px;
    }

    .po-line-head .line-title {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .po-line-head .line-title strong {
        font-size: 0.9rem;
        color: #0f172a;
    }

    .po-line-head .line-title span {
        font-size: 0.78rem;
        color: #64748b;
    }

    .procurement-search-row {
        margin-bottom: 12px;
    }

    .procurement-inline-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1.15fr);
        gap: 12px;
        align-items: end;
    }

    .procurement-field label,
    .procurement-stacked-field label {
        font-size: 0.78rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: 6px;
        white-space: nowrap;
    }

    .procurement-field .form-control,
    .procurement-field .form-select,
    .procurement-stacked-field .form-control,
    .procurement-stacked-field .form-select {
        min-height: 42px;
    }

    .procurement-mini-result {
        font-size: 0.78rem;
        color: #64748b;
        margin-top: 6px;
        min-height: 18px;
    }

    .procurement-total-field {
        background: #f8fafc;
        border-color: rgba(37,99,235,0.18);
        font-weight: 700;
        color: #0f172a;
        text-align: right;
    }

    .procurement-rate-box {
        border: 1px solid rgba(148,163,184,0.16);
        border-radius: 14px;
        padding: 10px 12px;
        background: rgba(248,250,252,0.74);
        font-size: 0.8rem;
        color: #475569;
        line-height: 1.55;
    }

    .procurement-rate-box strong {
        color: #0f172a;
        font-weight: 700;
    }

    .procurement-kpi {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .procurement-kpi div {
        padding: 12px 14px;
        border-radius: 16px;
        background: rgba(37,99,235,0.06);
        border: 1px solid rgba(37,99,235,0.10);
        font-size: 0.78rem;
        color: #64748b;
    }

    .procurement-kpi strong {
        display: block;
        font-size: 1.02rem;
        margin-top: 4px;
        color: #0f172a;
    }

    .procurement-grn-grid {
        display: grid;
        grid-template-columns: 1.1fr 1fr;
        gap: 14px;
    }

    .procurement-grn-stats {
        display: flex;
        flex-direction: column;
        gap: 14px;
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

    .procurement-table-card .card-body {
        padding-top: 12px;
    }

    .procurement-table-card .table th {
        white-space: nowrap;
        font-size: 0.74rem;
    }

    .procurement-table-card .table td {
        vertical-align: top;
    }

    @media (max-width: 1199.98px) {
        .procurement-top-grid,
        .procurement-bottom-grid,
        .procurement-grn-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 991.98px) {
        .procurement-inline-grid,
        .procurement-kpi {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
@php
    $procurementPoLinesJson = $pos->mapWithKeys(function ($po) {
        return [
            $po->id => $po->lines->map(function ($line) {
                return [
                    'id' => $line->id,
                    'item_id' => $line->item_id,
                    'item_label' => trim(($line->item?->sku ? $line->item->sku.' — ' : '').($line->item?->name ?? 'Unknown Item').($line->item?->uom ? ' ('.$line->item->uom.')' : '')),
                    'ordered' => number_format((float) $line->qty_ordered, 3, '.', ''),
                    'received' => number_format((float) ($line->received_qty ?? 0), 3, '.', ''),
                    'pending' => number_format((float) ($line->pending_qty ?? 0), 3, '.', ''),
                ];
            })->values()->all(),
        ];
    })->all();

    $procurementItemsJson = $items->map(function ($i) use ($poRateHistory, $grnRateHistory) {
        $poHistory = $poRateHistory[$i->id] ?? ['last_po_rate' => null, 'last_po_date' => null];
        $grnHistory = $grnRateHistory[$i->id] ?? ['last_grn_rate' => null, 'last_grn_date' => null, 'recent_grn_rates' => [], 'avg_grn_rate' => null];

        return [
            'id' => $i->id,
            'label' => trim(($i->sku ? $i->sku.' — ' : '').$i->name.($i->uom ? ' ('.$i->uom.')' : '').($i->category ? ' · '.$i->category : '')),
            'search' => strtolower(trim(($i->sku ?? '').' '.$i->name.' '.($i->category ?? '').' '.($i->uom ?? ''))),
            'base_uom' => $i->uom,
            'po_history' => $poHistory,
            'grn_history' => $grnHistory,
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
<div class="procurement-shell">
    <div class="procurement-top-grid">
        <div class="card shadow-sm procurement-card">
            <div class="card-header"><div class="procurement-header-title"><strong>Vendor Setup</strong><span>Add supplier master quickly before PO entry.</span></div></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.procurement.vendors.store') }}" class="procurement-form-grid">@csrf
                    <div class="procurement-stacked-field">
                        <label>Vendor Name</label>
                        <input name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Enter vendor name" required value="{{ old('name') }}">
                    </div>
                    @error('name')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                    <div class="procurement-note">Create vendors here, then use them immediately in the PO form.</div>
                    <div><button class="btn btn-primary">Create Vendor</button></div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm procurement-card">
            <div class="card-header"><div class="procurement-header-title"><strong>Create Purchase Order</strong><span>Build readable PO lines with live totals and compact cost history.</span></div></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.procurement.po.store') }}" class="procurement-form-grid" id="po-form">@csrf
                    <div class="procurement-grn-grid">
                        <div class="procurement-stacked-field">
                            <label>Vendor</label>
                            <select name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror" required><option value="">Select vendor</option>@foreach($vendors as $v)<option value="{{ $v->id }}" @selected((string) old('vendor_id') === (string) $v->id)>{{ $v->name }}</option>@endforeach</select>
                        </div>
                        <div class="procurement-stacked-field">
                            <label>PO Date</label>
                            <input type="date" name="po_date" class="form-control @error('po_date') is-invalid @enderror" required value="{{ old('po_date') }}">
                        </div>
                    </div>
                    @error('vendor_id')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                    @error('po_date')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                    @error('lines')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                    <div>
                        <div class="procurement-action-row mb-2">
                            <div>
                                <div class="procurement-section-label mb-0">PO Line Editor</div>
                                <div class="procurement-note">Item search stays separate; quantity, unit rate, and total stay aligned.</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-po-line">Add Line</button>
                        </div>
                        <div class="po-lines" id="po-lines"></div>
                    </div>
                    <div class="procurement-action-row">
                        <div class="procurement-note">Only unit price is stored. Total amount is display-only.</div>
                        <button class="btn btn-primary" id="po-submit-btn">Create PO</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm procurement-card">
            <div class="card-header"><div class="procurement-header-title"><strong>Create GRN</strong><span>Receive against PO with compact stats, rate hints, and live total preview.</span></div></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.procurement.grn.store') }}" class="procurement-form-grid" id="grn-form">@csrf
                    <div class="procurement-grn-grid">
                        <div class="procurement-stacked-field">
                            <label>Purchase Order</label>
                            <select name="purchase_order_id" id="grn-po-select" class="form-select @error('purchase_order_id') is-invalid @enderror" required>
                                <option value="">Select PO</option>
                                @foreach($pos as $po)
                                    <option value="{{ $po->id }}" data-lines='@json($procurementPoLinesJson[$po->id] ?? [])' @selected((string) old('purchase_order_id') === (string) $po->id)>
                                        {{ $po->po_number }} — {{ $po->vendor->name ?? 'Vendor' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('purchase_order_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="procurement-stacked-field">
                            <label>PO Line / Item</label>
                            <select id="grn-line-select" class="form-select @error('purchase_order_line_id') is-invalid @enderror" required>
                                <option value="">Select PO first</option>
                            </select>
                            @error('purchase_order_line_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="procurement-stacked-field">
                        <label>Selected Item</label>
                        <input type="hidden" name="purchase_order_line_id" id="grn-line-id" required value="{{ old('purchase_order_line_id') }}">
                        <input type="text" id="grn-item-display" class="form-control" readonly placeholder="Select PO line first">
                        <input type="hidden" name="item_id" id="grn-item-id" required value="{{ old('item_id') }}">
                        @error('item_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        <div class="procurement-note mt-2">Stock posts on GRN create. Approval acknowledgement does not post again.</div>
                        <div class="text-danger small mt-1 d-none" id="grn-block-message">Receive quantity cannot exceed pending quantity.</div>
                    </div>

                    <div class="procurement-grn-stats">
                        <div class="procurement-kpi">
                            <div>Ordered Qty<strong id="grn-ordered">0.000</strong></div>
                            <div>Already Received<strong id="grn-received">0.000</strong></div>
                            <div>Pending Qty<strong id="grn-pending">0.000</strong></div>
                        </div>
                    </div>

                    <div class="procurement-inline-grid">
                        <div class="procurement-field">
                            <label>Received Date</label>
                            <input type="date" name="received_date" class="form-control @error('received_date') is-invalid @enderror" required value="{{ old('received_date') }}">
                        </div>
                        <div class="procurement-field">
                            <label>Qty Received</label>
                            <input type="number" step="0.001" min="0.001" name="qty_received" id="grn-qty-input" class="form-control @error('qty_received') is-invalid @enderror" required value="{{ old('qty_received') }}">
                        </div>
                        <div class="procurement-field">
                            <label>Unit</label>
                            <select name="unit_code" id="grn-unit-select" class="form-select @error('unit_code') is-invalid @enderror" required>
                                <option value="">Select unit</option>
                            </select>
                            <div class="procurement-mini-result" id="grn-conversion-preview"></div>
                        </div>
                    </div>
                    @error('received_date')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                    @error('qty_received')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                    @error('unit_code')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror

                    <div class="procurement-inline-grid">
                        <div class="procurement-field">
                            <label>Unit Cost</label>
                            <input type="number" step="0.01" min="0.01" name="unit_cost" id="grn-unit-cost-input" class="form-control @error('unit_cost') is-invalid @enderror" placeholder="Enter unit cost" required value="{{ old('unit_cost') }}">
                        </div>
                        <div class="procurement-field" style="grid-column: span 2;">
                            <label>Total Amount</label>
                            <input type="text" id="grn-total-amount" class="form-control procurement-total-field" value="0.00" readonly>
                        </div>
                    </div>
                    @error('unit_cost')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror

                    <div class="procurement-rate-box" id="grn-rate-history-box">PO Rate: <strong>—</strong><br>Last GRN Rate: <strong>—</strong><br>Last 3 GRN Rates: <strong>—</strong><br>Average GRN Rate: <strong>—</strong></div>

                    <div class="procurement-action-row">
                        <div class="procurement-note">Only unit cost is stored. Total amount is preview-only.</div>
                        <button class="btn btn-primary" id="grn-submit-btn">Create GRN</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="procurement-bottom-grid">
        <div class="card shadow-sm procurement-card procurement-table-card"><div class="card-header"><div class="procurement-header-title"><strong>Purchase Orders</strong><span>Recent PO activity with quantities, totals, and receipt status.</span></div></div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>PO Number</th><th>Date</th><th>Vendor</th><th>Total Lines</th><th>Total Qty</th><th>Total Amount</th><th>Received Qty</th><th>Pending Qty</th><th>Status</th><th>Actions</th></tr></thead><tbody>
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

        <div class="card shadow-sm procurement-card procurement-table-card"><div class="card-header"><div class="procurement-header-title"><strong>GRNs</strong><span>Recent goods receipts with received quantity and stored unit rate.</span></div></div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>GRN Number</th><th>Date</th><th>PO Number</th><th>Vendor</th><th>Item</th><th>Qty Received</th><th>Unit Cost</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        @foreach($grns as $grn)
            @php
                $grnLine = $grn->lines->first();
            @endphp
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
    </tbody></table></div></div>
    </div>
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
                    <div class="line-title">
                        <strong>PO Line ${poLineIndex + 1}</strong>
                        <span>Select item first, then confirm quantity and unit rate.</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-po-line">Remove</button>
                </div>
                <div class="procurement-search-row procurement-stacked-field">
                    <label>Search Item</label>
                    <input type="text" class="form-control po-line-search" list="procurement-items-list" placeholder="Search by item code, item name, category" autocomplete="off" required>
                    <input type="hidden" name="lines[${poLineIndex}][item_id]" class="po-line-item-id" required>
                    <div class="procurement-mini-result">Pick an item from the searchable list.</div>
                </div>
                <div class="procurement-inline-grid">
                    <div class="procurement-field">
                        <label>Qty Ordered</label>
                        <input type="number" step="0.001" min="0.001" name="lines[${poLineIndex}][qty_ordered]" class="form-control po-line-qty" required>
                    </div>
                    <div class="procurement-field">
                        <label>Unit Price</label>
                        <input type="number" step="0.01" min="0.01" name="lines[${poLineIndex}][unit_price]" class="form-control po-line-rate" required>
                    </div>
                    <div class="procurement-field">
                        <label>Total Amount</label>
                        <input type="text" class="form-control po-line-total procurement-total-field" value="0.00" readonly>
                    </div>
                </div>
                <div class="procurement-rate-box mt-2 po-history-box">Last PO Rate: <strong>—</strong><br>Last GRN Rate: <strong>—</strong><br>Last 3 GRN Rates: <strong>—</strong><br>Average GRN Rate: <strong>—</strong></div>
            `;

            const searchInput = line.querySelector('.po-line-search');
            const hiddenInput = line.querySelector('.po-line-item-id');
            const result = line.querySelector('.procurement-mini-result');
            const qtyInput = line.querySelector('.po-line-qty');
            const rateInput = line.querySelector('.po-line-rate');
            const totalInput = line.querySelector('.po-line-total');
            const historyBox = line.querySelector('.po-history-box');

            const syncTotal = () => {
                const qty = Number(qtyInput.value || 0);
                const rate = Number(rateInput.value || 0);
                totalInput.value = (qty * rate).toFixed(2);
            };

            const syncHistory = (match) => {
                const poRate = match?.po_history?.last_po_rate;
                const poDate = match?.po_history?.last_po_date;
                const grnRate = match?.grn_history?.last_grn_rate;
                const grnDate = match?.grn_history?.last_grn_date;
                const recent = (match?.grn_history?.recent_grn_rates || []).map(entry => `${entry.received_date}: ${Number(entry.unit_cost).toFixed(2)}`).join(', ') || '—';
                const avg = match?.grn_history?.avg_grn_rate;
                historyBox.innerHTML = `Last PO Rate: <strong>${poRate !== null ? Number(poRate).toFixed(2)+' ('+poDate+')' : '—'}</strong><br>Last GRN Rate: <strong>${grnRate !== null ? Number(grnRate).toFixed(2)+' ('+grnDate+')' : '—'}</strong><br>Last 3 GRN Rates: <strong>${recent}</strong><br>Average GRN Rate: <strong>${avg !== null ? Number(avg).toFixed(2) : '—'}</strong>`;
            };

            const sync = () => {
                const match = resolveItem(searchInput.value);
                if (match) {
                    hiddenInput.value = match.id;
                    searchInput.value = match.label;
                    result.textContent = match.label;
                    result.style.color = '#64748b';
                    syncHistory(match);
                } else {
                    syncHistory(null);
                    hiddenInput.value = '';
                    result.textContent = searchInput.value.trim() ? 'Pick an exact item from the search list.' : 'Pick an item from the searchable list.';
                    result.style.color = '#64748b';
                }
                refreshDuplicateState();
            };

            searchInput.addEventListener('change', sync);
            searchInput.addEventListener('blur', sync);
            qtyInput.addEventListener('input', syncTotal);
            rateInput.addEventListener('input', syncTotal);
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
        const grnUnitCostInput = document.getElementById('grn-unit-cost-input');
        const grnTotalAmount = document.getElementById('grn-total-amount');
        const grnRateHistoryBox = document.getElementById('grn-rate-history-box');

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
                grnUnitCostInput.value = '';
                grnTotalAmount.value = '0.00';
                grnRateHistoryBox.innerHTML = 'PO Rate: <strong>—</strong><br>Last GRN Rate: <strong>—</strong><br>Last 3 GRN Rates: <strong>—</strong><br>Average GRN Rate: <strong>—</strong>';
                grnOrdered.textContent = '0.000';
                grnReceived.textContent = '0.000';
                grnPending.textContent = '0.000';
                grnQtyInput.removeAttribute('max');
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
            const item = itemsById[Number(line.dataset.itemId || 0)] || null;
            const poRate = item?.po_history?.last_po_rate;
            const grnRate = item?.grn_history?.last_grn_rate;
            const recent = (item?.grn_history?.recent_grn_rates || []).map(entry => `${entry.received_date}: ${Number(entry.unit_cost).toFixed(2)}`).join(', ') || '—';
            const avg = item?.grn_history?.avg_grn_rate;
            grnRateHistoryBox.innerHTML = `PO Rate: <strong>${poRate !== null ? Number(poRate).toFixed(2) : '—'}</strong><br>Last GRN Rate: <strong>${grnRate !== null ? Number(grnRate).toFixed(2) : '—'}</strong><br>Last 3 GRN Rates: <strong>${recent}</strong><br>Average GRN Rate: <strong>${avg !== null ? Number(avg).toFixed(2) : '—'}</strong>`;
            if (poRate !== null) {
                grnUnitCostInput.value = Number(poRate).toFixed(2);
            }
            grnOrdered.textContent = line.dataset.ordered || '0.000';
            grnReceived.textContent = line.dataset.received || '0.000';
            grnPending.textContent = line.dataset.pending || '0.000';
            grnQtyInput.max = line.dataset.pending || '';

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
            syncGrnTotal();
        };

        const syncGrnTotal = () => {
            if (!grnTotalAmount) return;
            const qty = Number(grnQtyInput.value || 0);
            const rate = Number(grnUnitCostInput.value || 0);
            grnTotalAmount.value = (qty * rate).toFixed(2);
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
            syncGrnTotal();
        });
        grnUnitSelect?.addEventListener('change', syncGrnConversion);
        grnUnitCostInput?.addEventListener('input', syncGrnTotal);

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

        syncPo();
        syncGrnQtyGuard();
    })();
</script>
@endpush
