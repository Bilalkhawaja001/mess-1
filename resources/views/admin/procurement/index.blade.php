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
    #poModal .procurement-line-grid {
        grid-template-columns: minmax(0, 1fr) 88px 108px !important;
        gap: 10px;
        align-items: start !important;
    }
    #poModal .procurement-line-grid > div { display: flex; flex-direction: column; }
    #poModal .procurement-line-grid .form-label { min-height: 18px; margin-bottom: 6px; }
    #poModal .procurement-mini-result { margin-top: 4px; min-height: 14px; }
    #poModal .po-line-card { padding: 12px; }
    #poModal .procurement-mini-result { font-size: 11px; }

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

{{-- Procurement flash messages --}}
@if(session('success'))
    <div class="alert alert-success mb-3">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger mb-3">{{ session('error') }}</div>
@endif

@if(session('warning'))
    <div class="alert alert-warning mb-3">{{ session('warning') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger mb-3">
        <strong>Validation errors:</strong>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif


@php
    $activeTab = request('tab', 'po');
    if (! in_array($activeTab, ['vendors', 'po', 'grn', 'reports', 'datewise'], true)) {
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
    $vendorCount = $vendors->count();
    $poCount = $pos->count();
    $grnCount = $grns->count();
    $grnEligibleCount = $grnEligiblePos->count();
@endphp

<div class="procurement-page-shell">
    <div class="page-hero page-hero-compact mb-4">
        <div>
            <h1 class="page-hero-title">Procurement</h1>
        </div>
    </div>

    <div class="stats-grid stats-grid-4 mb-4">
        <div class="stat-card stat-card-primary">
            <div class="stat-label">Vendors</div>
            <div class="stat-value">{{ $vendorCount }}</div>
            <div class="stat-help">Vendor master records</div>
        </div>
        <div class="stat-card stat-card-success">
            <div class="stat-label">Purchase Orders</div>
            <div class="stat-value">{{ $poCount }}</div>
            <div class="stat-help">Visible PO records</div>
        </div>
        <div class="stat-card stat-card-info">
            <div class="stat-label">GRNs</div>
            <div class="stat-value">{{ $grnCount }}</div>
            <div class="stat-help">Receiving documents</div>
        </div>
        <div class="stat-card stat-card-warning">
            <div class="stat-label">Open for GRN</div>
            <div class="stat-value">{{ $grnEligibleCount }}</div>
            <div class="stat-help">POs still eligible for receipt</div>
        </div>
    </div>

    <div class="procurement-toolbar">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="procurement-section-title">Procurement</div>
            </div>
        </div>

        <div class="d-flex procurement-tab-nav">
            <a href="{{ route('admin.procurement.index', ['tab' => 'vendors']) }}" class="procurement-tab-link {{ $activeTab === 'vendors' ? 'active' : '' }}">Vendors</a>
            <a href="{{ route('admin.procurement.index', ['tab' => 'po']) }}" class="procurement-tab-link {{ $activeTab === 'po' ? 'active' : '' }}">Purchase Orders</a>
            <a href="{{ route('admin.procurement.index', ['tab' => 'grn']) }}" class="procurement-tab-link {{ $activeTab === 'grn' ? 'active' : '' }}">GRNs / Receiving</a>
            <a href="{{ route('admin.procurement.index', ['tab' => 'reports']) }}" class="procurement-tab-link {{ $activeTab === 'reports' ? 'active' : '' }}">Purchase Reports</a>
            <a href="{{ route('admin.procurement.index', ['tab' => 'datewise']) }}" class="procurement-tab-link {{ $activeTab === 'datewise' ? 'active' : '' }}">Date wise Purchase</a>
        </div>
    </div>

    @if($activeTab === 'vendors')
        <div class="procurement-tab-panel">
            <div style="font-family:'Inter',system-ui,sans-serif;color:#191c1e">

                {{-- KPI ROW --}}
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px">
                    <div style="background:#fff;border:1px solid #e0e3e5;border-radius:8px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.1),0 1px 2px rgba(0,0,0,.06);display:flex;flex-direction:column">
                        <span style="font-size:12px;font-weight:600;letter-spacing:.05em;color:#545f72;text-transform:uppercase;margin-bottom:8px">Total Vendors</span>
                        <span style="font-size:20px;font-weight:600;color:#041632;margin-top:auto">{{ $vendors->count() }}</span>
                    </div>
                    <div style="background:#fff;border:1px solid #e0e3e5;border-radius:8px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.1),0 1px 2px rgba(0,0,0,.06);display:flex;flex-direction:column">
                        <span style="font-size:12px;font-weight:600;letter-spacing:.05em;color:#545f72;text-transform:uppercase;margin-bottom:8px">Purchase Orders</span>
                        <span style="font-size:20px;font-weight:600;color:#041632;margin-top:auto">{{ $pos->count() }}</span>
                    </div>
                    <div style="background:#fff;border:1px solid #e0e3e5;border-radius:8px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.1),0 1px 2px rgba(0,0,0,.06);display:flex;flex-direction:column">
                        <span style="font-size:12px;font-weight:600;letter-spacing:.05em;color:#545f72;text-transform:uppercase;margin-bottom:8px">Total GRNs</span>
                        <span style="font-size:20px;font-weight:600;color:#041632;margin-top:auto">{{ $grns->count() }}</span>
                    </div>
                    <div style="background:#fff;border:1px solid #e0e3e5;border-radius:8px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.1),0 1px 2px rgba(0,0,0,.06);display:flex;flex-direction:column">
                        <span style="font-size:12px;font-weight:600;letter-spacing:.05em;color:#545f72;text-transform:uppercase;margin-bottom:8px">POs Awaiting Receipt</span>
                        <span style="font-size:20px;font-weight:600;color:#041632;margin-top:auto">{{ $pos->whereIn('status', ['APPROVED','PARTIALLY_RECEIVED'])->count() }}</span>
                    </div>
                </div>

                {{-- TWO-COLUMN: Add Vendor + Vendor List --}}
                <div style="display:grid;grid-template-columns:1fr;gap:24px;align-items:start" class="proc-vendor-grid">
                    <style>@media(min-width:992px){.proc-vendor-grid{grid-template-columns:1fr 2fr !important}}</style>

                    {{-- Add Vendor --}}
                    <div style="background:#fff;border:1px solid #e0e3e5;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.1),0 1px 2px rgba(0,0,0,.06);overflow:hidden">
                        <div style="padding:20px;border-bottom:1px solid #e0e3e5;background:#f7f9fb">
                            <h2 style="font-size:20px;font-weight:600;color:#041632;margin:0 0 4px">Add Vendor</h2>
                            <p style="font-size:12px;color:#545f72;margin:0">Create a vendor for purchase orders</p>
                        </div>
                        <div style="padding:20px">
                            <form method="POST" action="{{ route('admin.procurement.vendors.store') }}">
                                @csrf
                                <div style="margin-bottom:16px">
                                    <label style="display:block;font-size:12px;font-weight:600;letter-spacing:.05em;color:#191c1e;text-transform:uppercase;margin-bottom:8px">Vendor Name <span style="color:#ba1a1a">*</span></label>
                                    <input name="name" required value="{{ old('name') }}" placeholder="Enter vendor name"
                                           style="width:100%;padding:9px 12px;border:1px solid {{ $errors->has('name') ? '#ba1a1a' : '#c5c6ce' }};border-radius:4px;font-size:14px;color:#191c1e;outline:none;box-sizing:border-box">
                                    @error('name')<div style="color:#ba1a1a;font-size:12px;margin-top:6px">{{ $message }}</div>@enderror
                                </div>
                                <button type="submit" style="width:100%;background:#041632;color:#fff;border:none;font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;padding:10px 16px;border-radius:4px;cursor:pointer">Add Vendor</button>
                            </form>
                        </div>
                    </div>

                    {{-- Vendor List --}}
                    <div style="background:#fff;border:1px solid #e0e3e5;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.1),0 1px 2px rgba(0,0,0,.06);overflow:hidden">
                        <div style="padding:20px;border-bottom:1px solid #e0e3e5;background:#f7f9fb;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
                            <div>
                                <h2 style="font-size:20px;font-weight:600;color:#041632;margin:0">Vendor List</h2>
                                <p style="font-size:12px;color:#545f72;margin:4px 0 0">Total: {{ $vendors->count() }}</p>
                            </div>
                            <div style="position:relative;max-width:260px;width:100%">
                                <span class="material-symbols-outlined" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#545f72;font-size:18px">search</span>
                                <input id="vendorSearch" onkeyup="vendorFilter()" placeholder="Search vendors..."
                                       style="width:100%;padding:8px 12px 8px 34px;border:1px solid #c5c6ce;border-radius:4px;font-size:12px;outline:none;box-sizing:border-box">
                            </div>
                        </div>

                        @if($vendors->isEmpty())
                            <div style="padding:48px 24px;text-align:center;color:#545f72">
                                <span class="material-symbols-outlined" style="font-size:40px;color:#c5c6ce;display:block;margin-bottom:12px">storefront</span>
                                <p style="font-size:14px;margin:0">No vendors have been added yet.</p>
                            </div>
                        @else
                            <div style="overflow-x:auto">
                                <table style="width:100%;border-collapse:collapse" id="vendorTable">
                                    <thead>
                                        <tr style="background:#f2f4f6;border-bottom:1px solid #e0e3e5">
                                            <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:600;letter-spacing:.05em;color:#44474d;text-transform:uppercase">Name</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($vendors as $vendor)
                                            <tr style="border-bottom:1px solid #e0e3e5" onmouseover="this.style.background='#f7f9fb'" onmouseout="this.style.background='#fff'">
                                                <td style="padding:11px 16px;font-size:13px;color:#191c1e">{{ $vendor->name }}</td>
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
        <script>
        function vendorFilter(){
            var q=(document.getElementById('vendorSearch').value||'').toLowerCase();
            var rows=document.querySelectorAll('#vendorTable tbody tr');
            rows.forEach(function(r){
                r.style.display = r.textContent.toLowerCase().indexOf(q)>-1 ? '' : 'none';
            });
        }
        </script>
    @elseif($activeTab === 'po')
        <div class="procurement-tab-panel">
            <div style="font-family:'Inter',system-ui,sans-serif">

            {{-- Action bar: New PO button --}}
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-bottom:16px">
                <button type="button" onclick="poModalOpen()"
                        style="height:36px;padding:0 18px;background:#041632;color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px">
                    <span class="material-symbols-outlined" style="font-size:18px">add</span> New PO
                </button>
            </div>

            {{-- CREATE PO MODAL (centered popup) --}}
            <div id="poModal" onclick="if(event.target===this)poModalClose()"
                 style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:1050;align-items:flex-start;justify-content:center;padding:170px 16px 40px;overflow-y:auto">
                <div style="background:#fff;width:720px;max-width:100%;border-radius:10px;box-shadow:0 20px 50px rgba(0,0,0,.2);display:flex;flex-direction:column;max-height:calc(100vh - 80px);font-family:'Inter',sans-serif">
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 22px;border-bottom:1px solid #e0e3e5;background:#f7f9fb;border-radius:10px 10px 0 0">
                        <h2 style="font-size:16px;font-weight:600;color:#041632;margin:0">Create Purchase Order</h2>
                        <button type="button" onclick="poModalClose()" style="border:none;background:none;font-size:22px;cursor:pointer;color:#545f72;line-height:1">&times;</button>
                    </div>
                    <div style="flex:1;overflow-y:auto;padding:22px">
                        <form method="POST" action="{{ route('admin.procurement.po.store') }}?tab=po" id="po-form">@csrf
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
                                <div>
                                    <label style="display:block;font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#545f72;margin-bottom:6px">Vendor <span style="color:#ba1a1a">*</span></label>
                                    <select name="vendor_id" required
                                            style="width:100%;height:36px;padding:0 10px;border:1px solid {{ $errors->has('vendor_id') ? '#ba1a1a' : '#c5c6ce' }};border-radius:4px;font-size:13px;background:#fff;box-sizing:border-box">
                                        <option value="">Select vendor</option>
                                        @foreach($vendors as $v)
                                            <option value="{{ $v->id }}" @selected((string) old('vendor_id') === (string) $v->id)>{{ $v->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('vendor_id')<div style="color:#ba1a1a;font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label style="display:block;font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#545f72;margin-bottom:6px">PO Date <span style="color:#ba1a1a">*</span></label>
                                    <input type="date" name="po_date" required value="{{ old('po_date') }}"
                                           style="width:100%;height:36px;padding:0 10px;border:1px solid {{ $errors->has('po_date') ? '#ba1a1a' : '#c5c6ce' }};border-radius:4px;font-size:13px;box-sizing:border-box">
                                    @error('po_date')<div style="color:#ba1a1a;font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div style="display:flex;justify-content:space-between;align-items:center;padding-bottom:8px;border-bottom:1px solid #e0e3e5;margin-bottom:12px">
                                <span style="font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#545f72">PO Lines</span>
                                <button type="button" id="add-po-line"
                                        style="border:none;background:none;color:#041632;font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;cursor:pointer;display:inline-flex;align-items:center;gap:4px">
                                    + Add Line
                                </button>
                            </div>
                            <div class="po-lines" id="po-lines"></div>
                            @error('lines')<div style="color:#ba1a1a;font-size:12px;margin-top:8px">{{ $message }}</div>@enderror
                        </form>
                    </div>
                    <div style="padding:16px 22px;border-top:1px solid #e0e3e5;background:#f7f9fb;display:flex;justify-content:flex-end;gap:10px;border-radius:0 0 10px 10px">
                        <button type="button" onclick="poModalClose()" style="height:36px;padding:0 16px;background:#fff;border:1px solid #c5c6ce;border-radius:4px;font-size:13px;cursor:pointer">Cancel</button>
                        <button type="submit" form="po-form" id="po-submit-btn" style="height:36px;padding:0 18px;background:#041632;color:#fff;border:none;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer">Create PO</button>
                    </div>
                </div>
            </div>

            <script>
            function poModalOpen(){document.getElementById('poModal').style.display='flex';}
            function poModalClose(){document.getElementById('poModal').style.display='none';}
            @if($errors->any() && request('tab')==='po')poModalOpen();@endif
            </script>

            <div class="procurement-grid" style="margin-top:0">
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
                            @if(isset($poImportPreview['po_groups']))
                                <div class="procurement-import-summary">
                                    <span class="badge text-bg-light">Total rows: {{ $poImportPreview['total_rows'] ?? 0 }}</span>
                                    <span class="badge text-bg-light">Vendors: {{ $poImportPreview['vendor_count'] ?? 0 }}</span>
                                    <span class="badge text-bg-light">PO groups: {{ $poImportPreview['group_count'] ?? 0 }}</span>
                                    <span class="badge text-bg-success">Valid rows: {{ $poImportPreview['valid_count'] ?? 0 }}</span>
                                    <span class="badge text-bg-danger">Error rows: {{ $poImportPreview['error_count'] ?? 0 }}</span>
                                    <span class="badge text-bg-light">Total value: {{ number_format((float) ($poImportPreview['total_value'] ?? 0), 2) }}</span>
                                </div>

                                @if(!empty($poImportPreview['po_groups']))
                                    @foreach($poImportPreview['po_groups'] as $gi => $group)
                                        <div class="card mb-3">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <span>PO {{ $gi + 1 }}: {{ $group['vendor_name'] }} — {{ $group['po_date'] }}</span>
                                                <span class="text-muted small">{{ $group['row_count'] }} rows · Qty {{ number_format((float) $group['total_qty'], 3) }} · Value {{ number_format((float) $group['total_value'], 2) }}</span>
                                            </div>
                                            <div class="card-body p-2">
                                                <div class="table-responsive">
                                                    <table class="table table-sm align-middle mb-0">
                                                        <thead><tr><th>#</th><th>SKU</th><th>Item</th><th>Qty</th><th>Unit Price</th><th>Remarks</th></tr></thead>
                                                        <tbody>
                                                            @foreach($group['rows'] as $row)
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
                                            </div>
                                        </div>
                                    @endforeach

                                    @if(empty($poImportPreview['error_rows']))
                                        <form method="POST" action="{{ route('admin.procurement.po.import.store') }}">
                                            @csrf
                                            <button class="btn btn-success w-100">Create All POs ({{ $poImportPreview['group_count'] ?? 0 }})</button>
                                        </form>
                                    @else
                                        <div class="text-danger small mb-2">Fix error rows below before creating POs. No PO will be created while errors exist.</div>
                                    @endif
                                    <form method="POST" action="{{ route('admin.procurement.po.import.cancel') }}" class="d-inline ms-2">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary">Cancel Preview</button>
                                    </form>
                                @endif
                            @else
                                <div class="text-warning small mb-2">Preview format outdated (old session). Clear preview and re-upload the CSV.</div>
                                <form method="POST" action="{{ route('admin.procurement.po.import.cancel') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary">Cancel Preview</button>
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

            <div style="font-family:'Inter',system-ui,sans-serif;color:#191c1e;margin-top:24px">
                <div style="background:#fff;border:1px solid #e0e3e5;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.1),0 1px 2px rgba(0,0,0,.06);overflow:hidden">
                    <form method="POST" action="{{ route('admin.procurement.po.bulk-approve') }}?tab=po" id="po-bulk-form">
                        @csrf

                        {{-- Toolbar --}}
                        <div style="padding:16px 20px;border-bottom:1px solid #e0e3e5;background:#f7f9fb;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
                            <div style="display:flex;align-items:center;gap:12px">
                                <h2 style="font-size:20px;font-weight:600;color:#041632;margin:0">PO Register</h2>
                                <span style="font-size:12px;color:#545f72"><span id="po-selected-count">0</span> selected</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                                <select onchange="window.location='{{ route('admin.procurement.index') }}?tab=po&po_status='+this.value"
                                        style="height:34px;padding:0 12px;border:1px solid #c5c6ce;border-radius:4px;font-size:13px;background:#fff;color:#191c1e;cursor:pointer">
                                    <option value="" @selected(($poStatusFilter ?? '') === '')>All statuses</option>
                                    <option value="DRAFT" @selected(($poStatusFilter ?? '') === 'DRAFT')>Draft</option>
                                    <option value="APPROVED" @selected(($poStatusFilter ?? '') === 'APPROVED')>Approved</option>
                                    <option value="PARTIALLY_RECEIVED" @selected(($poStatusFilter ?? '') === 'PARTIALLY_RECEIVED')>Partially Received</option>
                                    <option value="RECEIVED" @selected(($poStatusFilter ?? '') === 'RECEIVED')>Received</option>
                                    <option value="CANCELLED" @selected(($poStatusFilter ?? '') === 'CANCELLED')>Cancelled</option>
                                </select>
                                <button type="submit" id="po-bulk-submit" disabled
                                        style="height:34px;padding:0 16px;border:1px solid #041632;background:#fff;color:#041632;font-size:12px;font-weight:600;letter-spacing:.03em;text-transform:uppercase;border-radius:4px;cursor:pointer;opacity:.5">Bulk Approve</button>
                            </div>
                        </div>

                        {{-- Table --}}
                        <div style="overflow-x:auto">
                            <table style="width:100%;border-collapse:collapse;min-width:1080px">
                                <thead>
                                    <tr style="background:#f2f4f6;border-bottom:1px solid #e0e3e5">
                                        <th style="width:44px;padding:11px 16px;text-align:center"><input type="checkbox" id="po-select-all"></th>
                                        <th style="padding:11px 16px;text-align:left;font-size:11px;font-weight:600;letter-spacing:.05em;color:#44474d;text-transform:uppercase">PO Number</th>
                                        <th style="padding:11px 16px;text-align:left;font-size:11px;font-weight:600;letter-spacing:.05em;color:#44474d;text-transform:uppercase">Date</th>
                                        <th style="padding:11px 16px;text-align:left;font-size:11px;font-weight:600;letter-spacing:.05em;color:#44474d;text-transform:uppercase">Vendor</th>
                                        <th style="padding:11px 16px;text-align:center;font-size:11px;font-weight:600;letter-spacing:.05em;color:#44474d;text-transform:uppercase">Lines</th>
                                        <th style="padding:11px 16px;text-align:right;font-size:11px;font-weight:600;letter-spacing:.05em;color:#44474d;text-transform:uppercase">Total Qty</th>
                                        <th style="padding:11px 16px;text-align:right;font-size:11px;font-weight:600;letter-spacing:.05em;color:#44474d;text-transform:uppercase">PO Value</th>
                                        <th style="padding:11px 16px;text-align:left;font-size:11px;font-weight:600;letter-spacing:.05em;color:#44474d;text-transform:uppercase">Receipt Progress</th>
                                        <th style="padding:11px 16px;text-align:center;font-size:11px;font-weight:600;letter-spacing:.05em;color:#44474d;text-transform:uppercase">Status</th>
                                        <th style="padding:11px 16px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($pos as $po)
                                    @php
                                        $poSelectable = in_array($po->status, ['DRAFT', 'ISSUED'], true);
                                        $ordered = (float) ($po->total_qty ?? 0);
                                        $received = (float) ($po->received_qty ?? 0);
                                        $pct = $ordered > 0 ? min(100, round(($received / $ordered) * 100)) : 0;
                                        $statusMap = [
                                            'DRAFT'              => ['#64748b','#f1f5f9'],
                                            'ISSUED'             => ['#1d4ed8','#dbeafe'],
                                            'APPROVED'           => ['#1d4ed8','#dbeafe'],
                                            'PARTIALLY_RECEIVED' => ['#b45309','#fef3c7'],
                                            'RECEIVED'           => ['#15803d','#dcfce7'],
                                            'CANCELLED'          => ['#57534e','#f5f5f4'],
                                        ];
                                        $sc = $statusMap[$po->status] ?? ['#57534e','#f5f5f4'];
                                        $barColor = $pct >= 100 ? '#15803d' : ($pct > 0 ? '#b45309' : '#c5c6ce');
                                    @endphp
                                    <tr style="border-bottom:1px solid #e0e3e5" onmouseover="this.style.background='#f7f9fb';this.querySelector('.po-actions').style.opacity=1" onmouseout="this.style.background='#fff';this.querySelector('.po-actions').style.opacity=0">
                                        <td style="padding:11px 16px;text-align:center">
                                            @if($poSelectable)<input type="checkbox" class="po-row-check" name="po_ids[]" value="{{ $po->id }}">@endif
                                        </td>
                                        <td style="padding:11px 16px;font-size:13px;font-weight:500;color:#041632">{{ $po->po_number }}</td>
                                        <td style="padding:11px 16px;font-size:13px;color:#191c1e;white-space:nowrap">{{ $po->po_date }}</td>
                                        <td style="padding:11px 16px;font-size:13px;color:#191c1e">
                                            <div>{{ $po->vendor->name ?? '-' }}</div>
                                            <div style="font-size:11px;color:#8a8d93;margin-top:2px">
                                                @foreach($po->lines->take(2) as $line){{ $line->item?->sku }}@if(!$loop->last), @endif @endforeach
                                            </div>
                                        </td>
                                        <td style="padding:11px 16px;text-align:center;font-size:13px;color:#545f72">{{ $po->total_lines }}</td>
                                        <td style="padding:11px 16px;text-align:right;font-size:13px;color:#191c1e;font-variant-numeric:tabular-nums">{{ number_format($ordered, 3) }}</td>
                                        <td style="padding:11px 16px;text-align:right;font-size:13px;font-weight:500;color:#191c1e;font-variant-numeric:tabular-nums">{{ number_format((float) ($po->total_amount ?? 0), 2) }}</td>
                                        <td style="padding:11px 16px">
                                            <div style="width:130px">
                                                <div style="display:flex;justify-content:space-between;font-size:10px;color:#8a8d93;margin-bottom:3px;font-variant-numeric:tabular-nums"><span>{{ number_format($received, 0) }}</span><span>{{ number_format($ordered, 0) }}</span></div>
                                                <div style="width:100%;height:6px;background:#e0e3e5;border-radius:9999px;overflow:hidden"><div style="height:100%;width:{{ $pct }}%;background:{{ $barColor }}"></div></div>
                                            </div>
                                        </td>
                                        <td style="padding:11px 16px;text-align:center">
                                            <span style="display:inline-block;padding:3px 9px;border-radius:4px;font-size:10px;font-weight:700;letter-spacing:.03em;text-transform:uppercase;color:{{ $sc[0] }};background:{{ $sc[1] }}">{{ str_replace('_',' ',$po->status) }}</span>
                                        </td>
                                        <td style="padding:11px 16px;text-align:right;white-space:nowrap">
                                            <span class="po-actions" style="opacity:0;transition:opacity .1s;display:inline-flex;gap:6px;justify-content:flex-end">
                                                @if($poSelectable)
                                                    <button type="submit" formaction="{{ route('admin.procurement.po.approve',$po) }}?tab=po" formmethod="POST" style="border:1px solid #15803d;background:#fff;color:#15803d;font-size:11px;font-weight:600;padding:4px 10px;border-radius:4px;cursor:pointer">Approve</button>
                                                @endif
                                                @if($po->goodsReceipts->isEmpty() && $po->status !== 'CANCELLED')
                                                    <a href="{{ route('admin.procurement.index', ['tab' => 'po', 'edit_po' => $po->id]) }}" style="border:1px solid #041632;background:#fff;color:#041632;font-size:11px;font-weight:600;padding:4px 10px;border-radius:4px;text-decoration:none">Edit</a>
                                                    <button type="submit" formaction="{{ route('admin.procurement.po.cancel',$po) }}?tab=po" formmethod="POST" onclick="return confirm('Cancel this PO? This is allowed only before GRN creation.');" style="border:1px solid #ba1a1a;background:#fff;color:#ba1a1a;font-size:11px;font-weight:600;padding:4px 10px;border-radius:4px;cursor:pointer">Cancel</button>
                                                @endif
                                                @if($po->goodsReceipts->isNotEmpty())
                                                    <span style="font-size:11px;color:#8a8d93">GRN Created</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10" style="padding:48px 24px;text-align:center;color:#545f72;font-size:14px">No purchase orders found.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>

                    @if(isset($editPo) && $editPo)
                        <div class="card procurement-form-card mt-3">
                            <div class="card-header">
                                <span>Edit PO Lines</span>
                                <span class="text-muted small">{{ $editPo->po_number }} — {{ $editPo->po_date }} — {{ $editPo->vendor->name ?? 'Vendor' }}</span>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('admin.procurement.po.lines.update', $editPo) }}?tab=po">
                                    @csrf
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Item Code</th>
                                                    <th>Item Name</th>
                                                    <th>Qty</th>
                                                    <th>Unit Price</th>
                                                    <th>Remove</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($editPo->lines as $line)
                                                    <tr>
                                                        <td>{{ $line->item?->sku }}</td>
                                                        <td>{{ $line->item?->name }}</td>
                                                        <td><input type="number" step="0.001" min="0.001" name="lines[{{ $line->id }}][qty_ordered]" value="{{ $line->qty_ordered }}" class="form-control form-control-sm"></td>
                                                        <td><input type="number" step="0.01" min="0" name="lines[{{ $line->id }}][unit_price]" value="{{ $line->unit_price }}" class="form-control form-control-sm"></td>
                                                        <td class="text-center"><input type="checkbox" name="lines[{{ $line->id }}][remove]" value="1"></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="{{ route('admin.procurement.index', ['tab' => 'po']) }}" class="btn btn-outline-secondary">Close</a>
                                        <button type="submit" class="btn btn-primary">Save PO Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @elseif($activeTab === 'grn')
        <div class="procurement-tab-panel">
            <div class="procurement-grid">
                <div class="card procurement-form-card">
                    <div class="card-header"><span>Create GRN</span><span class="text-muted small">Receive into stock</span></div>
                    <div class="card-body">
                        <div class="mb-3" id="grn-po-cards-wrap">
                          <div class="procurement-group-label">Pending Purchase Orders</div>
                          <div class="procurement-note mb-2">Click a PO to receive its items.</div>
                          <div class="table-responsive" id="grn-po-cards">
                            <table class="table table-sm table-hover align-middle mb-0">
                              <thead class="table-light"><tr>
                                <th>PO Number</th><th>Date</th><th>Vendor</th><th class="text-end">Items</th><th></th>
                              </tr></thead>
                              <tbody>
                                @forelse($grnEligiblePos as $po)
                                  <tr class="grn-po-card" style="cursor:pointer" data-po-id="{{ $po->id }}" data-po-date="{{ $po->po_date }}">
                                    <td class="fw-semibold">{{ $po->po_number }}</td>
                                    <td>{{ $po->po_date }}</td>
                                    <td>{{ $po->vendor->name ?? 'Vendor' }}</td>
                                    <td class="text-end">{{ $po->lines->count() }}</td>
                                    <td class="text-end"><button type="button" class="btn btn-sm btn-primary grn-po-open">Receive</button></td>
                                  </tr>
                                @empty
                                  <tr><td colspan="5" class="text-muted small">No pending POs.</td></tr>
                                @endforelse
                              </tbody>
                            </table>
                          </div>
                        </div>

                        <div class="modal fade" id="grnReceiveModal" tabindex="-1" aria-hidden="true">
                          <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title" id="grnReceiveModalTitle">Receive Items</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                              </div>
                              <div class="modal-body" id="grnReceiveModalBody"></div>
                            </div>
                          </div>
                        </div>

<script>
(function(){
  function initGrnPopup(){
    var form = document.getElementById('grn-form');
    var poSelect = document.getElementById('grn-po-select');
    var modalEl = document.getElementById('grnReceiveModal');
    var modalBody = document.getElementById('grnReceiveModalBody');
    if (!form || !poSelect || !modalEl || !modalBody) return;
    var formHome = form.parentNode; // remember original location
    var modalInstance = null;

    document.querySelectorAll('.grn-po-card').forEach(function(card){
      card.addEventListener('click', function(){
        var poId = this.getAttribute('data-po-id');
        poSelect.value = poId;
        poSelect.dispatchEvent(new Event('change')); // existing JS renders lines
        var poDate = this.getAttribute('data-po-date');
        var dateInput = document.getElementById('grn-received-date');
        if (poDate && dateInput) dateInput.value = poDate;

        // add Select-All if not present
        if (!document.getElementById('grn-select-all-wrap')){
          var wrap = document.createElement('div');
          wrap.id = 'grn-select-all-wrap';
          wrap.className = 'mb-2';
          wrap.innerHTML = '<label class="d-inline-flex align-items-center gap-2"><input type="checkbox" id="grn-select-all"> <b>Select All Items</b></label>';
          form.insertBefore(wrap, form.firstChild);
          wrap.querySelector('#grn-select-all').addEventListener('change', function(){
            var on = this.checked;
            form.querySelectorAll('.grn-row-selected').forEach(function(cb){ cb.checked = on; });
          });
        }

        // move whole form into modal (intact) and show
        modalBody.appendChild(form);
        if (modalEl.parentNode !== document.body) document.body.appendChild(modalEl);
        modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modalInstance.show();
      });
    });

    // when modal closes, move form back home (so nothing is lost)
    modalEl.addEventListener('hidden.bs.modal', function(){
      if (form && formHome) formHome.appendChild(form);
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initGrnPopup);
  else initGrnPopup();
})();
</script>

                        <form method="POST" action="{{ route('admin.procurement.grn.store') }}?tab=grn" class="row g-3" id="grn-form">@csrf
                            <div class="col-12">
                                <label class="form-label">Purchase Order</label>
                                <select name="purchase_order_id" id="grn-po-select" class="form-select @error('purchase_order_id') is-invalid @enderror" required>
                                    <option value="">Select PO</option>
                                    @foreach($grnEligiblePos as $po)
                                        <option value="{{ $po->id }}" data-po-date="{{ $po->po_date }}" data-lines='@json($procurementPoLinesJson[$po->id] ?? [])' @selected((string) old('purchase_order_id') === (string) $po->id)>
                                            {{ $po->po_number }} — {{ $po->po_date }} — {{ $po->vendor->name ?? 'Vendor' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('purchase_order_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Receive date</label>
                                <input type="date" name="received_date" id="grn-received-date" class="form-control @error('received_date') is-invalid @enderror" required value="{{ old('received_date') }}">
                                @error('received_date')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    var poSelect = document.getElementById('grn-po-select');
                                    var dateInput = document.getElementById('grn-received-date');
                                    if (!poSelect || !dateInput) return;
                                    poSelect.addEventListener('change', function () {
                                        var opt = poSelect.options[poSelect.selectedIndex];
                                        var poDate = opt ? opt.getAttribute('data-po-date') : '';
                                        if (poDate) { dateInput.value = poDate; }
                                    });
                                });
                            </script>
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
                                        <option value="{{ $po->id }}" @selected((string) $selectedGrnTemplatePo === (string) $po->id)>{{ $po->po_number }} — {{ $po->po_date }} — {{ $po->vendor->name ?? 'Vendor' }}</option>
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
                            @if(isset($grnImportPreview['grn_groups']))
                                <div class="procurement-import-summary">
                                    <span class="badge text-bg-light">Total rows: {{ $grnImportPreview['total_rows'] ?? 0 }}</span>
                                    <span class="badge text-bg-light">POs: {{ $grnImportPreview['po_count'] ?? 0 }}</span>
                                    <span class="badge text-bg-light">GRN groups: {{ $grnImportPreview['group_count'] ?? 0 }}</span>
                                    <span class="badge text-bg-success">Valid rows: {{ $grnImportPreview['valid_count'] ?? 0 }}</span>
                                    <span class="badge text-bg-danger">Error rows: {{ $grnImportPreview['error_count'] ?? 0 }}</span>
                                    <span class="badge text-bg-light">Total value: {{ number_format((float) ($grnImportPreview['total_value'] ?? 0), 2) }}</span>
                                </div>

                                @if(!empty($grnImportPreview['grn_groups']))
                                    @foreach($grnImportPreview['grn_groups'] as $gi => $group)
                                        <div class="card mb-3">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <span>GRN {{ $gi + 1 }}: {{ $group['po_number'] }} — {{ $group['vendor_name'] }} — {{ $group['received_date'] }}</span>
                                                <span class="text-muted small">{{ $group['row_count'] }} rows · Qty {{ number_format((float) $group['total_qty'], 3) }} · Value {{ number_format((float) $group['total_value'], 2) }}</span>
                                            </div>
                                            <div class="card-body p-2">
                                                <div class="table-responsive">
                                                    <table class="table table-sm align-middle mb-0">
                                                        <thead><tr><th>#</th><th>SKU</th><th>Item</th><th>Pending</th><th>Receive Qty</th><th>Unit Cost</th><th>Unit</th><th>Remarks</th></tr></thead>
                                                        <tbody>
                                                            @foreach($group['rows'] as $row)
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
                                            </div>
                                        </div>
                                    @endforeach

                                    @if(empty($grnImportPreview['error_rows']))
                                        <form method="POST" action="{{ route('admin.procurement.grn.import.store') }}">
                                            @csrf
                                            <button class="btn btn-success w-100">Post All GRNs ({{ $grnImportPreview['group_count'] ?? 0 }})</button>
                                        </form>
                                    @else
                                        <div class="text-danger small mb-2">Fix error rows below before posting GRNs. No GRN will be posted while errors exist.</div>
                                    @endif
                                    <form method="POST" action="{{ route('admin.procurement.grn.import.cancel') }}" class="d-inline ms-2">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary">Cancel Preview</button>
                                    </form>
                                @endif
                            @else
                                <div class="text-warning small mb-2">Preview format outdated (old session). Clear preview and re-upload the CSV.</div>
                                <form method="POST" action="{{ route('admin.procurement.grn.import.cancel') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary">Cancel Preview</button>
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
                                            <td class="text-end">
                                                <button type="submit" formaction="{{ route('admin.procurement.grn.approve',$grn) }}?tab=grn" formmethod="POST" class="btn btn-sm btn-outline-success">Acknowledge</button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="grnReverse({{ $grn->id }}, '{{ $grn->grn_number }}')">Reverse</button>
                                            </td>
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
    @elseif($activeTab === 'reports')
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

                    <form method="GET" action="{{ route('admin.procurement.reports.export-selected') }}" target="procurement-download-frame" class="row g-3 mb-3">
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

            <div class="card procurement-table-card">
                <div class="card-header">
                    <span>GRN Details</span>
                    <span class="text-muted small">Line-wise GRN purchase details</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>GRN</th>
                                <th>PO</th>
                                <th>Vendor</th>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Net Qty</th>
                                <th>UOM</th>
                                <th>Unit Cost</th>
                                <th>Net Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchaseReportData['grnDetails'] ?? $purchaseReportData['grn_details'] ?? [] as $row)
                                <tr>
                                    <td>{{ $row->received_date ?? '' }}</td>
                                    <td>{{ $row->grn_number ?? ('GRN#'.$row->grn_id) ?? '' }}</td>
                                    <td>{{ $row->po_number ?? ('PO#'.$row->po_id) ?? '' }}</td>
                                    <td>{{ $row->vendor_name ?? '' }}</td>
                                    <td>{{ $row->sku ?? $row->item_code ?? '' }}</td>
                                    <td>{{ $row->item_name ?? '' }}</td>
                                    <td>{{ $row->category ?? '' }}</td>
                                    <td>{{ number_format((float) ($row->net_qty ?? 0), 3) }}</td>
                                    <td>{{ $row->uom ?? '' }}</td>
                                    <td>{{ number_format((float) ($row->unit_cost ?? 0), 2) }}</td>
                                    <td>{{ number_format((float) ($row->total_cost ?? 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">No GRN details found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

    @endif

    @if($activeTab === 'datewise')
    <div class="procurement-tab-panel" id="datewise-panel">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Date wise Purchase</span>
                <span class="text-muted small">Total purchasing grouped by date {{ $reportSearch ? '(filtered: '.$reportSearch.')' : '' }}</span>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.procurement.index') }}" class="row g-2 mb-3">
                    <input type="hidden" name="tab" value="datewise">
                    <div class="col-md-3"><label class="form-label small mb-1">From</label>
                        <input type="date" name="report_from" value="{{ $reportFromDate }}" class="form-control form-control-sm"></div>
                    <div class="col-md-3"><label class="form-label small mb-1">To</label>
                        <input type="date" name="report_to" value="{{ $reportToDate }}" class="form-control form-control-sm"></div>
                    <div class="col-md-4"><label class="form-label small mb-1">Item / Vendor</label>
                        <input type="text" name="report_search" value="{{ $reportSearch }}" placeholder="item, code, category or vendor" class="form-control form-control-sm"></div>
                    <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-primary btn-sm w-100">Apply</button></div>
                </form>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">GRNs</th>
                                <th class="text-end">POs</th>
                                <th class="text-end">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $grandTotal = 0; @endphp
                            @forelse(($purchaseReportData['dateRows'] ?? []) as $dr)
                                @php $grandTotal += (float) $dr->total_cost; @endphp
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($dr->received_date)->format('d-M-Y') }}</td>
                                    <td class="text-end">{{ number_format((float) $dr->total_qty, 2) }}</td>
                                    <td class="text-end">{{ $dr->grn_count }}</td>
                                    <td class="text-end">{{ $dr->po_count }}</td>
                                    <td class="text-end">
                                        <a href="#" class="datewise-amount fw-semibold text-decoration-none" data-date="{{ $dr->received_date }}">
                                            {{ number_format((float) $dr->total_cost, 2) }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">No purchases in this range.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">Grand Total</th>
                                <th class="text-end">{{ number_format($grandTotal, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="datewiseModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="datewiseModalTitle">Purchase Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
              <table class="table table-sm">
                <thead class="table-light"><tr>
                  <th>Item</th><th>Vendor</th><th>GRN</th><th class="text-end">Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th>
                </tr></thead>
                <tbody id="datewiseModalBody"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script>
    (function(){
      var DETAILS = @json($purchaseReportData['grnDetails'] ?? []);
      var modalEl = document.getElementById('datewiseModal');
      if (!modalEl) return;
      var modal = null;
      document.querySelectorAll('.datewise-amount').forEach(function(a){
        a.addEventListener('click', function(e){
          e.preventDefault();
          var d = this.getAttribute('data-date');
          var rows = DETAILS.filter(function(r){ return String(r.received_date).slice(0,10) === d; });
          var body = document.getElementById('datewiseModalBody');
          document.getElementById('datewiseModalTitle').textContent = 'Purchases on ' + d;
          if (!rows.length) { body.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No line details (may exceed detail limit).</td></tr>'; }
          else {
            body.innerHTML = rows.map(function(r){
              return '<tr><td>'+ (r.item_name||'') +' <span class="text-muted">('+ (r.item_code||'') +')</span></td>'+
                     '<td>'+ (r.vendor_name||'') +'</td>'+
                     '<td>'+ (r.grn_number||'') +'</td>'+
                     '<td class="text-end">'+ Number(r.net_qty).toFixed(2) +'</td>'+
                     '<td class="text-end">'+ Number(r.unit_cost).toFixed(2) +'</td>'+
                     '<td class="text-end">'+ Number(r.total_cost).toFixed(2) +'</td></tr>';
            }).join('');
          }
          if (modalEl.parentNode !== document.body) { document.body.appendChild(modalEl); }
          if (!modal) { modal = new bootstrap.Modal(modalEl); }
          modal.show();
        });
      });
    })();
    </script>
    @endif

</div>

<datalist id="procurement-items-list">
    @foreach($items as $i)
        <option value="{{ $i->sku }} — {{ $i->name }}{{ $i->uom ? ' ('.$i->uom.')' : '' }}{{ $i->category ? ' · '.$i->category : '' }}" data-item-id="{{ $i->id }}"></option>
    @endforeach
</datalist>
<form method="POST" id="grnReverseForm" action="">
@csrf
<input type="hidden" name="reason" id="grnReverseReason">
</form>
<script>
function grnReverse(id, num){
  var reason = window.prompt("Reverse GRN " + num + "?\n\nThis rolls back the stock posted by this GRN. Enter a reason (min 5 chars):");
  if(reason === null) return;
  reason = reason.trim();
  if(reason.length < 5){ alert("Reason must be at least 5 characters."); return; }
  var f = document.getElementById("grnReverseForm");
  f.action = "{{ url('/admin/procurement/grn') }}/" + id + "/reverse?tab=grn";
  document.getElementById("grnReverseReason").value = reason;
  f.submit();
}
</script>
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

        function triggerProcurementDownload(url) {
            let frame = document.getElementById('procurement-download-frame');

            if (!frame) {
                frame = document.createElement('iframe');
                frame.id = 'procurement-download-frame';
                frame.name = 'procurement-download-frame';
                frame.style.display = 'none';
                document.body.appendChild(frame);
            }

            frame.src = url;
        }

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

                triggerProcurementDownload(`${baseUrl}?${params.toString()}`);
            });
        }


</script>
<iframe id="procurement-download-frame" name="procurement-download-frame" style="display:none;"></iframe>
@endpush
