@extends('layouts.app')
@section('title','Procurement')
@section('page_title','Procurement')

@push('styles')
<style>
    .procurement-note {
        font-size: 0.84rem;
        color: #64748b;
    }

    .procurement-page-shell {
        max-width: 1400px;
    }

    .procurement-toolbar {
        border-radius: 20px;
        padding: 1rem 1rem 0.95rem;
        background: linear-gradient(135deg, rgba(15,23,42,0.02), rgba(37,99,235,0.05));
        border: 1px solid rgba(148,163,184,0.28);
        margin-bottom: 1rem;
    }

    .procurement-tab-nav {
        gap: 0.65rem;
        margin-top: 0.9rem;
        flex-wrap: wrap;
    }

    .procurement-tab-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        padding: 0.65rem 1rem;
        border-radius: 999px;
        border: 1px solid rgba(148,163,184,0.35);
        background: #fff;
        color: #334155;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .procurement-tab-link.active {
        background: #0f172a;
        color: #fff;
        border-color: #0f172a;
        box-shadow: 0 10px 24px rgba(15,23,42,0.16);
    }

    .procurement-tab-panel {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .procurement-form-card,
    .procurement-table-card,
    .procurement-vendor-table-card {
        border: 1px solid rgba(148,163,184,0.22);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 12px 30px rgba(15,23,42,0.06);
    }

    .procurement-form-card .card-header,
    .procurement-table-card .card-header,
    .procurement-vendor-table-card .card-header {
        padding: 0.9rem 1rem;
        background: #fff;
        border-bottom: 1px solid rgba(148,163,184,0.18);
        font-size: 0.94rem;
        font-weight: 700;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .procurement-form-card .card-body,
    .procurement-table-card .card-body,
    .procurement-vendor-table-card .card-body {
        padding: 1rem;
        background: #fff;
    }

    .procurement-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        align-items: start;
    }

    .procurement-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        align-items: start;
    }

    .procurement-group-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        padding-top: 0.45rem;
        margin-bottom: 0.35rem;
        border-top: 1px dashed rgba(148,163,184,0.45);
    }

    .procurement-kpi {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-top: 12px;
        padding: 0.65rem 0.75rem;
        border-radius: 16px;
        background: rgba(15,23,42,0.02);
        border: 1px solid rgba(148,163,184,0.35);
    }

    .procurement-kpi div {
        font-size: 0.82rem;
        display: flex;
        justify-content: space-between;
        gap: 10px;
        color: #475569;
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
        gap: 12px;
        margin-bottom: 10px;
        font-size: 0.85rem;
        font-weight: 700;
        color: #334155;
    }

    .procurement-line-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(120px, 0.7fr) minmax(120px, 0.7fr) auto;
        gap: 12px;
        align-items: end;
    }

    .procurement-line-grid .form-label,
    .procurement-form-card .form-label {
        font-size: 0.82rem;
        margin-bottom: 0.35rem;
        font-weight: 600;
        color: #334155;
    }

    .procurement-form-card .form-control,
    .procurement-form-card .form-select,
    .procurement-form-card .btn,
    .procurement-table-card .btn,
    .procurement-vendor-table-card .btn {
        min-height: 42px;
        border-radius: 12px;
    }

    .procurement-form-card .btn,
    .procurement-table-card .btn,
    .procurement-vendor-table-card .btn {
        font-weight: 600;
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
        padding: 0.55rem 0.8rem;
        border-radius: 14px;
        background: rgba(15,23,42,0.02);
        border: 1px dashed rgba(148,163,184,0.45);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
        flex-wrap: wrap;
    }

    .procurement-table-card .table,
    .procurement-vendor-table-card .table {
        font-size: 0.82rem;
        margin-bottom: 0;
    }

    .procurement-empty {
        padding: 1rem;
        border-radius: 14px;
        background: rgba(248,250,252,0.8);
        border: 1px dashed rgba(148,163,184,0.4);
        color: #64748b;
        text-align: center;
        font-size: 0.88rem;
    }

    .bulk-po-upload,
    .bulk-grn-upload,
    .import-preview {
        width: 100%;
    }

    .procurement-import-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .procurement-import-summary {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 0.85rem;
    }

    .procurement-import-summary .badge {
        padding: 0.6rem 0.8rem;
        border-radius: 999px;
        font-size: 0.8rem;
    }

    @media (max-width: 1199.98px) {
        .procurement-grid,
        .procurement-form-grid,
        .procurement-import-grid {
            grid-template-columns: 1fr;
        }
    }

    .procurement-section-kicker {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #64748b;
    }

    .procurement-section-title {
        font-size: 1.06rem;
        font-weight: 700;
        margin-bottom: 0.4rem;
    }

    .procurement-section-sub {
        font-size: 0.84rem;
        color: #64748b;
        margin-bottom: 0;
    }

    @media (max-width: 1199.98px) {
        .procurement-form-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 991.98px) {
        .procurement-line-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 575.98px) {
        .procurement-toolbar {
            padding: 0.9rem 0.85rem;
        }

        .procurement-form-card .card-body,
        .procurement-table-card .card-body,
        .procurement-vendor-table-card .card-body {
            padding: 0.85rem;
        }
    }
</style>
@endpush

@section('content')
@php
    $activeTab = request('tab', 'po');
    if (! in_array($activeTab, ['vendors', 'po', 'grn', 'reports'], true)) {
        $activeTab = 'po';
    }

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

<div class="procurement-page-shell">
    <div class="procurement-toolbar">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="procurement-section-kicker">Procurement workspace</div>
                <div class="procurement-section-title">Vendors, Purchase Orders &amp; GRNs</div>
                <p class="procurement-section-sub">Manage vendors separately, raise purchase orders cleanly, and receive stock through a focused GRN workflow.</p>
            </div>
        </div>

        <div class="d-flex procurement-tab-nav">
            <a href="{{ route('admin.procurement.index', ['tab' => 'vendors']) }}" class="procurement-tab-link {{ $activeTab === 'vendors' ? 'active' : '' }}">Vendors</a>
            <a href="{{ route('admin.procurement.index', ['tab' => 'po']) }}" class="procurement-tab-link {{ $activeTab === 'po' ? 'active' : '' }}">Purchase Orders</a>
            <a href="{{ route('admin.procurement.index', ['tab' => 'grn']) }}" class="procurement-tab-link {{ $activeTab === 'grn' ? 'active' : '' }}">GRNs / Receiving</a>
            <a href="{{ route('admin.procurement.index', ['tab' => 'reports']) }}" class="procurement-tab-link {{ $activeTab === 'reports' ? 'active' : '' }}">Purchase Reports</a>
        </div>
    </div>

    @if($activeTab === 'vendors')
        <div class="procurement-tab-panel">
            <div class="card procurement-form-card">
                <div class="card-header"><span>Create Vendor</span><span class="text-muted small">Vendor master</span></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-xl-5">
                            <form method="POST" action="{{ route('admin.procurement.vendors.store') }}" class="row g-3">
                                @csrf
                                <div class="col-12">
                                    <label class="form-label">Vendor Name</label>
                                    <input name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Vendor name" required value="{{ old('name') }}">
                                </div>
                                @error('name')
                                    <div class="col-12"><div class="text-danger small">{{ $message }}</div></div>
                                @enderror
                                <div class="col-12">
                                    <button class="btn btn-primary w-100">Create Vendor</button>
                                </div>
                            </form>
                        </div>
                        <div class="col-xl-7">
                            <div class="card procurement-vendor-table-card h-100">
                                <div class="card-header"><span>Vendor List</span><span class="text-muted small">{{ $vendors->count() }} total</span></div>
                                <div class="card-body">
                                    @if($vendors->isEmpty())
                                        <div class="procurement-empty">No vendors added yet.</div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($vendors as $vendor)
                                                        <tr>
                                                            <td>{{ $vendor->name }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'po')
        <div class="procurement-tab-panel">
            <div class="procurement-grid">
                <div class="card procurement-form-card">
                    <div class="card-header"><span>Create PO</span><span class="text-muted small">Order lines</span></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.procurement.po.store') }}?tab=po" class="row g-3" id="po-form">@csrf
                            <div class="col-12">
                                <label class="form-label">Vendor</label>
                                <select name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror" required>
                                    <option value="">Select vendor</option>
                                    @foreach($vendors as $v)
                                        <option value="{{ $v->id }}" @selected((string) old('vendor_id') === (string) $v->id)>{{ $v->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('vendor_id')
                                <div class="col-12"><div class="text-danger small">{{ $message }}</div></div>
                            @enderror
                            <div class="col-12">
                                <div class="procurement-group-label">PO lines</div>
                                <div class="po-lines" id="po-lines"></div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-po-line">Add Line</button>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">PO Date</label>
                                <input type="date" name="po_date" class="form-control @error('po_date') is-invalid @enderror" required value="{{ old('po_date') }}">
                            </div>
                            @error('po_date')
                                <div class="col-12"><div class="text-danger small">{{ $message }}</div></div>
                            @enderror
                            @error('lines')
                                <div class="col-12"><div class="text-danger small">{{ $message }}</div></div>
                            @enderror
                            <div class="col-12 d-flex justify-content-end">
                                <button class="btn btn-primary px-4" id="po-submit-btn">Create PO</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card procurement-form-card bulk-po-upload">
                    <div class="card-header"><span>Bulk PO Upload</span><span class="text-muted small">CSV workflow</span></div>
                    <div class="card-body">
                        <div class="d-grid gap-2 mb-3">
                            <a href="{{ route('admin.procurement.po.template') }}" class="btn btn-outline-primary">Download PO Template</a>
                        </div>
                        <form method="POST" action="{{ route('admin.procurement.po.import.preview') }}" enctype="multipart/form-data" class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">Upload PO Items CSV</label>
                                <input type="file" name="po_import_file" accept=".csv,text/csv" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary w-100">Preview uploaded PO lines</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="procurement-import-grid">
                <div class="card procurement-table-card import-preview">
                    <div class="card-header"><span>PO Import Preview</span><span class="text-muted small">Validate before save</span></div>
                    <div class="card-body">
                        @if($poImportPreview)
                            <div class="procurement-import-summary">
                                <span class="badge text-bg-success">Valid rows: {{ $poImportPreview['valid_count'] ?? 0 }}</span>
                                <span class="badge text-bg-danger">Error rows: {{ $poImportPreview['error_count'] ?? 0 }}</span>
                                @if(!empty($poImportPreview['vendor_name']))
                                    <span class="badge text-bg-light">Vendor: {{ $poImportPreview['vendor_name'] }}</span>
                                @endif
                                @if(!empty($poImportPreview['po_date']))
                                    <span class="badge text-bg-light">PO Date: {{ $poImportPreview['po_date'] }}</span>
                                @endif
                            </div>

                            @if(!empty($poImportPreview['valid_rows']))
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm align-middle">
                                        <thead><tr><th>#</th><th>SKU</th><th>Item</th><th>Qty</th><th>Unit Price</th><th>Remarks</th></tr></thead>
                                        <tbody>
                                            @foreach($poImportPreview['valid_rows'] as $row)
                                                <tr>
                                                    <td>{{ $row['line_number'] }}</td>
                                                    <td>{{ $row['item_sku'] }}</td>
                                                    <td>{{ $row['item_name'] }}</td>
                                                    <td>{{ number_format((float) $row['qty_ordered'], 3) }}</td>
                                                    <td>{{ number_format((float) $row['unit_price'], 2) }}</td>
                                                    <td>{{ $row['remarks'] ?: '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <form method="POST" action="{{ route('admin.procurement.po.import.store') }}">
                                    @csrf
                                    <button class="btn btn-success w-100">Create PO from uploaded lines</button>
                                </form>
                            @endif

                            @if(!empty($poImportPreview['error_rows']))
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm align-middle">
                                        <thead><tr><th>#</th><th>Row Data</th><th>Errors</th></tr></thead>
                                        <tbody>
                                            @foreach($poImportPreview['error_rows'] as $row)
                                                <tr>
                                                    <td>{{ $row['line_number'] }}</td>
                                                    <td><code>{{ json_encode($row['data']) }}</code></td>
                                                    <td>{{ implode('; ', $row['errors']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        @else
                            <div class="procurement-empty">Upload CSV to preview PO lines before saving.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card procurement-table-card">
                <div class="card-header"><span>Purchase Orders</span><span class="text-muted small">Draft &amp; approved</span></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.procurement.po.bulk-approve') }}?tab=po" id="po-bulk-form">
                            @csrf
                            <div class="bulk-action-bar">
                                <div><span id="po-selected-count">0</span> PO(s) selected</div>
                                <button type="submit" class="btn btn-sm btn-outline-success" id="po-bulk-submit" disabled>Bulk Approve</button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle"><thead><tr><th><input type="checkbox" id="po-select-all"></th><th>PO Number</th><th>Date</th><th>Vendor</th><th>Total Lines</th><th>Total Qty</th><th>Total Amount</th><th>Received Qty</th><th>Pending Qty</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                                    @forelse($pos as $po)
                                        @php $poSelectable = in_array($po->status, ['DRAFT', 'ISSUED'], true); @endphp
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
                                                    <button type="submit" formaction="{{ route('admin.procurement.po.approve',$po) }}?tab=po" formmethod="POST" class="btn btn-sm btn-outline-success">Approve</button>
                                                @else
                                                    <span class="text-muted small">Approved</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="11" class="text-center text-muted py-4">No purchase orders found.</td></tr>
                                    @endforelse
                                </tbody></table>
                            </div>
                    </form>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'grn')
        <div class="procurement-tab-panel">
            <div class="procurement-grid">
                <div class="card procurement-form-card">
                    <div class="card-header"><span>Create GRN</span><span class="text-muted small">Receive into stock</span></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.procurement.grn.store') }}?tab=grn" class="row g-3" id="grn-form">@csrf
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
                            <div class="col-md-6">
                                <label class="form-label">Receive date</label>
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
                            <div class="col-12 d-flex justify-content-end">
                                <button class="btn btn-primary px-4" id="grn-submit-btn">Receive Selected Items</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card procurement-form-card bulk-grn-upload">
                    <div class="card-header"><span>Bulk GRN Upload</span><span class="text-muted small">CSV workflow</span></div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('admin.procurement.index') }}" class="row g-3 mb-3">
                            <input type="hidden" name="tab" value="grn">
                            <div class="col-md-6">
                                <label class="form-label">From Date</label>
                                <input type="date" name="from_date" value="{{ $grnFromDate }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">To Date</label>
                                <input type="date" name="to_date" value="{{ $grnToDate }}" class="form-control">
                            </div>
                            <div class="col-12 d-grid gap-2">
                                <button class="btn btn-outline-secondary">Apply</button>
                                <div class="d-flex gap-2 flex-wrap align-items-end">
                                    <div>
                                        <label class="form-label small mb-1">Download Type</label>
                                        <select id="grn-export-type" class="form-select form-select-sm">
                                            <option value="detail">GRN Detail</option>
                                            <option value="summary">Item Summary</option>
                                        </select>
                                    </div>

                                    <button type="button" id="grn-export-download-btn" class="btn btn-outline-primary btn-sm">Download</button>
                                </div>
                            </div>
                        </form>
                        <form method="GET" action="{{ route('admin.procurement.grn.template') }}" class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label">PO for template (optional)</label>
                                <select name="purchase_order_id" class="form-select">
                                    <option value="">Blank GRN template</option>
                                    @foreach($grnEligiblePos as $po)
                                        <option value="{{ $po->id }}" @selected((string) $selectedGrnTemplatePo === (string) $po->id)>{{ $po->po_number }} — {{ $po->vendor->name ?? 'Vendor' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-outline-primary w-100">Download GRN Template</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('admin.procurement.grn.import.preview') }}" enctype="multipart/form-data" class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">Upload GRN Items CSV</label>
                                <input type="file" name="grn_import_file" accept=".csv,text/csv" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary w-100">Preview uploaded GRN lines</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="procurement-import-grid">
                <div class="card procurement-table-card import-preview">
                    <div class="card-header"><span>GRN Import Preview</span><span class="text-muted small">Validate before post</span></div>
                    <div class="card-body">
                        @if($grnImportPreview)
                            <div class="procurement-import-summary">
                                <span class="badge text-bg-success">Valid rows: {{ $grnImportPreview['valid_count'] ?? 0 }}</span>
                                <span class="badge text-bg-danger">Error rows: {{ $grnImportPreview['error_count'] ?? 0 }}</span>
                                @if(!empty($grnImportPreview['po_number']))
                                    <span class="badge text-bg-light">PO: {{ $grnImportPreview['po_number'] }}</span>
                                @endif
                                @if(!empty($grnImportPreview['received_date']))
                                    <span class="badge text-bg-light">Receive Date: {{ $grnImportPreview['received_date'] }}</span>
                                @endif
                            </div>

                            @if(!empty($grnImportPreview['valid_rows']))
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm align-middle">
                                        <thead><tr><th>#</th><th>SKU</th><th>Item</th><th>Pending</th><th>Receive Qty</th><th>Unit Cost</th><th>Unit</th><th>Remarks</th></tr></thead>
                                        <tbody>
                                            @foreach($grnImportPreview['valid_rows'] as $row)
                                                <tr>
                                                    <td>{{ $row['line_number'] }}</td>
                                                    <td>{{ $row['item_sku'] }}</td>
                                                    <td>{{ $row['item_name'] }}</td>
                                                    <td>{{ number_format((float) $row['pending_qty'], 3) }}</td>
                                                    <td>{{ number_format((float) $row['qty_received'], 3) }}</td>
                                                    <td>{{ number_format((float) $row['unit_cost'], 2) }}</td>
                                                    <td>{{ $row['unit_code'] }}</td>
                                                    <td>{{ $row['remarks'] ?: '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <form method="POST" action="{{ route('admin.procurement.grn.import.store') }}">
                                    @csrf
                                    <button class="btn btn-success w-100">Post GRN from uploaded lines</button>
                                </form>
                            @endif

                            @if(!empty($grnImportPreview['error_rows']))
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm align-middle">
                                        <thead><tr><th>#</th><th>Row Data</th><th>Errors</th></tr></thead>
                                        <tbody>
                                            @foreach($grnImportPreview['error_rows'] as $row)
                                                <tr>
                                                    <td>{{ $row['line_number'] }}</td>
                                                    <td><code>{{ json_encode($row['data']) }}</code></td>
                                                    <td>{{ implode('; ', $row['errors']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        @else
                            <div class="procurement-empty">Download a blank or PO-based template, then upload CSV to preview GRN received rows.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card procurement-table-card">
                <div class="card-header"><span>GRNs</span><span class="text-muted small">Posted on create</span></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.procurement.grn.bulk-approve') }}?tab=grn" id="grn-bulk-form">
                            @csrf
                            <div class="bulk-action-bar">
                                <div><span id="grn-selected-count">0</span> GRN(s) selected</div>
                                <button type="submit" class="btn btn-sm btn-outline-success" id="grn-bulk-submit" disabled>Bulk Acknowledge</button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle"><thead><tr><th><input type="checkbox" id="grn-select-all"></th><th>GRN Number</th><th>Date</th><th>PO Number</th><th>Vendor</th><th>Item</th><th>Qty Received</th><th>Unit Cost</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                                    @forelse($grns as $grn)
                                        @php $grnLine = $grn->lines->first(); @endphp
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
                                            <td class="text-end"><button type="submit" formaction="{{ route('admin.procurement.grn.approve',$grn) }}?tab=grn" formmethod="POST" class="btn btn-sm btn-outline-success">Acknowledge</button></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="10" class="text-center text-muted py-4">No GRNs found.</td></tr>
                                    @endforelse
                                </tbody></table>
                            </div>
                    </form>
                </div>
            </div>
        </div>
    @else
        <div class="procurement-tab-panel">
            <div class="card procurement-form-card">
                <div class="card-header"><span>Purchase Reports</span><span class="text-muted small">GRN-based purchasing analysis</span></div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.procurement.index') }}" class="row g-3 mb-3">
                        <input type="hidden" name="tab" value="reports">
                        <div class="col-md-3">
                            <label class="form-label">From Date</label>
                            <input type="date" name="from_date" value="{{ $reportFromDate }}" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to_date" value="{{ $reportToDate }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Search Item / Category / Vendor</label>
                            <input type="text" name="q" value="{{ $reportSearch }}" class="form-control" placeholder="e.g. Chicken">
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button class="btn btn-primary w-100" type="submit">Apply</button>
                        </div>
                    </form>

                    <form method="GET" action="{{ route('admin.procurement.reports.export-selected') }}" class="row g-3 mb-3">
                        <input type="hidden" name="tab" value="reports">
                        <input type="hidden" name="from_date" value="{{ $reportFromDate }}">
                        <input type="hidden" name="to_date" value="{{ $reportToDate }}">
                        <input type="hidden" name="q" value="{{ $reportSearch }}">
                        <div class="col-md-4">
                            <label class="form-label">Download Type</label>
                            <select name="report_type" class="form-select">
                                <option value="summary">Purchase Report Summary</option>
                                <option value="detail">GRN Detail</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button class="btn btn-outline-primary w-100" type="submit">Download</button>
                        </div>
                    </form>

                    <div class="procurement-kpi" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
                        <div><span>Total Purchasing Cost</span><strong>{{ number_format((float) ($purchaseReportData['totals']->total_cost ?? 0), 2) }}</strong></div>
                        <div><span>Total Purchased Qty</span><strong>{{ number_format((float) ($purchaseReportData['totals']->total_qty ?? 0), 3) }}</strong></div>
                        <div><span>Unique Items Purchased</span><strong>{{ (int) ($purchaseReportData['totals']->unique_items ?? 0) }}</strong></div>
                        <div><span>Vendors Used</span><strong>{{ (int) ($purchaseReportData['totals']->vendors_used ?? 0) }}</strong></div>
                    </div>
                </div>
            </div>

            <div class="card procurement-table-card">
                <div class="card-header"><span>Cost by Category</span><span class="text-muted small">Grouped purchase totals</span></div>
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Category</th><th>Total Qty</th><th>Total Cost</th><th>Avg Cost</th></tr></thead>
                        <tbody>
                            @forelse($purchaseReportData['categoryRows'] as $row)
                                <tr>
                                    <td>{{ $row->category }}</td>
                                    <td>{{ number_format((float) $row->total_qty, 3) }}</td>
                                    <td>{{ number_format((float) $row->total_cost, 2) }}</td>
                                    <td>{{ number_format((float) $row->avg_cost, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No category report rows found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card procurement-table-card">
                <div class="card-header"><span>Purchasing Cost by Vendor</span><span class="text-muted small">Vendor spend summary</span></div>
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Vendor</th><th>Total Qty</th><th>Total Cost</th><th>GRN Count</th></tr></thead>
                        <tbody>
                            @forelse($purchaseReportData['vendorRows'] as $row)
                                <tr>
                                    <td>{{ $row->vendor_name }}</td>
                                    <td>{{ number_format((float) $row->total_qty, 3) }}</td>
                                    <td>{{ number_format((float) $row->total_cost, 2) }}</td>
                                    <td>{{ (int) $row->grn_count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No vendor report rows found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card procurement-table-card">
                <div class="card-header"><span>Item Purchase Summary</span><span class="text-muted small">Item-wise purchased quantity and cost</span></div>
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Item Code</th><th>Item Name</th><th>Category</th><th>UOM</th><th>Total Qty</th><th>Total Cost</th><th>Avg Cost</th><th>First Date</th><th>Last Date</th></tr></thead>
                        <tbody>
                            @forelse($purchaseReportData['itemRows'] as $row)
                                <tr>
                                    <td>{{ $row->sku }}</td>
                                    <td>{{ $row->item_name }}</td>
                                    <td>{{ $row->category }}</td>
                                    <td>{{ $row->uom }}</td>
                                    <td>{{ number_format((float) $row->total_qty, 3) }}</td>
                                    <td>{{ number_format((float) $row->total_cost, 2) }}</td>
                                    <td>{{ number_format((float) $row->avg_cost, 2) }}</td>
                                    <td>{{ $row->first_grn_date }}</td>
                                    <td>{{ $row->last_grn_date }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-4">No item purchase summary rows found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
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
                if (note && input.value && duplicates.has(input.value)) {
                    note.textContent = 'Same item cannot be added twice in the same PO.';
                    note.style.color = '#dc2626';
                } else if (note && input.value) {
                    note.style.color = '#64748b';
                }
            });
        };

        const createPoLine = () => {
            if (!poLinesWrap) return;

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
        if (poLinesWrap) {
            createPoLine();
        }

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
            if (!poSelect || !grnLinesBody) return;

            const option = poSelect.selectedOptions?.[0];
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
                const hasOldRows = Object.keys(oldRowsByLineId).length > 0;
                const checked = hasOldRows ? !!oldRow.selected : true;
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

        if (poSelect && grnLinesBody) {
            renderGrnRows();
        }
    })();

        const grnExportBtn = document.getElementById('grn-export-download-btn');
        const grnExportType = document.getElementById('grn-export-type');

        if (grnExportBtn && grnExportType) {
            grnExportBtn.addEventListener('click', () => {
                const baseUrl = grnExportType.value === 'summary'
                    ? "{{ route('admin.procurement.grn.export.summary') }}"
                    : "{{ route('admin.procurement.grn.export.detail') }}";

                const params = new URLSearchParams({
                    from_date: "{{ $grnFromDate }}",
                    to_date: "{{ $grnToDate }}"
                });

                window.location.href = `${baseUrl}?${params.toString()}`;
            });
        }


        const purchaseReportDownloadType = document.getElementById('purchase-report-download-type');
        const purchaseReportDownloadBtn = document.getElementById('purchase-report-download-btn');

        if (purchaseReportDownloadType && purchaseReportDownloadBtn) {
            purchaseReportDownloadBtn.addEventListener('click', () => {
                const url = purchaseReportDownloadType.value === 'detail'
                    ? purchaseReportDownloadBtn.dataset.detailUrl
                    : purchaseReportDownloadBtn.dataset.summaryUrl;

                window.location.href = url;
            });
        }

</script>
@endpush
