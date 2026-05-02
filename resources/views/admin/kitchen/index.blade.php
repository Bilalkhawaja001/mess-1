@extends('layouts.app')

@section('title','Meal Planning / Kitchen')
@section('page_title','Meal Planning / Kitchen')

@push('styles')
<style>
    .kitchen-tabs-wrap {
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }
    .kitchen-tabs-nav {
        gap: .75rem;
        padding: 1rem;
        background: rgba(248, 250, 252, 0.95);
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        overflow-x: auto;
        flex-wrap: nowrap;
    }
    .kitchen-tabs-nav .nav-link {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 999px;
        color: #334155;
        background: #fff;
        font-weight: 600;
        white-space: nowrap;
        padding: .7rem 1rem;
    }
    .kitchen-tabs-nav .nav-link.active {
        background: #0d6efd;
        color: #fff;
        border-color: #0d6efd;
        box-shadow: 0 8px 20px rgba(13, 110, 253, 0.22);
    }
    .kitchen-tab-pane {
        padding: 1rem;
    }
    .kitchen-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    }
    .kitchen-card .card-header {
        background: #fff;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        font-weight: 700;
    }
    .kitchen-tab-kicker {
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: .35rem;
    }
    .kitchen-tab-title {
        font-size: 1.15rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: .2rem;
    }
    .kitchen-tab-subtitle {
        color: #64748b;
        margin-bottom: 1rem;
    }
    .kitchen-mini-stat {
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: .9rem;
        padding: .9rem 1rem;
        height: 100%;
    }
    .kitchen-mini-stat-label {
        color: #64748b;
        font-size: .82rem;
        margin-bottom: .2rem;
    }
    .kitchen-mini-stat-value {
        color: #0f172a;
        font-size: 1.1rem;
        font-weight: 800;
    }
    .table > :not(caption) > * > * {
        vertical-align: middle;
    }
    .kitchen-issue-form .form-control,
    .kitchen-issue-form .form-select {
        min-height: 50px;
    }
    .kitchen-field-date {
        min-width: 180px;
    }
    .kitchen-field-mess,
    .kitchen-field-item,
    .kitchen-field-type,
    .kitchen-field-unit {
        min-width: 170px;
    }
    .kitchen-field-qty {
        min-width: 120px;
    }
    .kitchen-field-remarks {
        min-width: 240px;
    }
    @media (min-width: 992px) {
        .kitchen-issue-form {
            align-items: start;
        }
    }
    @media (max-width: 767.98px) {
        .kitchen-tabs-nav {
            padding: .75rem;
        }
        .kitchen-tab-pane {
            padding: .75rem;
        }
        .kitchen-card .card-body,
        .kitchen-card .card-header {
            padding-left: .9rem;
            padding-right: .9rem;
        }
        .kitchen-field-date,
        .kitchen-field-mess,
        .kitchen-field-item,
        .kitchen-field-type,
        .kitchen-field-unit,
        .kitchen-field-qty,
        .kitchen-field-remarks {
            min-width: 100%;
        }
    }
</style>
@endpush

@section('content')
<div class="kitchen-tabs-wrap">
    <ul class="nav nav-pills kitchen-tabs-nav" id="kitchenTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ ($activeTab ?? 'issue') === 'issue' ? 'active' : '' }}" id="tab-issue-tab" data-bs-toggle="pill" data-bs-target="#tab-issue" type="button" role="tab" aria-controls="tab-issue" aria-selected="{{ ($activeTab ?? 'issue') === 'issue' ? 'true' : 'false' }}">
                <i class="bi bi-box-arrow-in-down-right me-1"></i> Post Kitchen Issue
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ ($activeTab ?? 'issue') === 'ledger' ? 'active' : '' }}" id="tab-ledger-tab" data-bs-toggle="pill" data-bs-target="#tab-ledger" type="button" role="tab" aria-controls="tab-ledger" aria-selected="{{ ($activeTab ?? 'issue') === 'ledger' ? 'true' : 'false' }}">
                <i class="bi bi-journal-text me-1"></i> Kitchen Issues & Summary / Ledger
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ ($activeTab ?? 'issue') === 'consumption-report' ? 'active' : '' }}" id="tab-consumption-report-tab" data-bs-toggle="pill" data-bs-target="#tab-consumption-report" type="button" role="tab" aria-controls="tab-consumption-report" aria-selected="{{ ($activeTab ?? 'issue') === 'consumption-report' ? 'true' : 'false' }}">
                <i class="bi bi-graph-up-arrow me-1"></i> Consumption Report
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ ($activeTab ?? 'issue') === 'menu' ? 'active' : '' }}" id="tab-menu-tab" data-bs-toggle="pill" data-bs-target="#tab-menu" type="button" role="tab" aria-controls="tab-menu" aria-selected="{{ ($activeTab ?? 'issue') === 'menu' ? 'true' : 'false' }}">
                <i class="bi bi-card-checklist me-1"></i> Menu / Recipes
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ ($activeTab ?? 'issue') === 'plans' ? 'active' : '' }}" id="tab-plans-tab" data-bs-toggle="pill" data-bs-target="#tab-plans" type="button" role="tab" aria-controls="tab-plans" aria-selected="{{ ($activeTab ?? 'issue') === 'plans' ? 'true' : 'false' }}">
                <i class="bi bi-calendar2-week me-1"></i> Meal Plans
            </button>
        </li>
    </ul>

    @if ($errors->any())
    <div class="alert alert-danger">
        <strong>Please fix the following:</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="tab-content" id="kitchenTabsContent">
        <div class="tab-pane fade {{ ($activeTab ?? 'issue') === 'issue' ? 'show active' : '' }} kitchen-tab-pane" id="tab-issue" role="tabpanel" aria-labelledby="tab-issue-tab" tabindex="0">
            <div class="kitchen-tab-kicker">Kitchen Ops</div>
            <div class="kitchen-tab-title">Post Kitchen Issue</div>
            <div class="kitchen-tab-subtitle">Create kitchen issue requests here. Stock deduction happens only after approval.</div>

            <div class="row g-3">
                <div class="col-12 col-xl-8">
                    <div class="card kitchen-card">
                        <div class="card-header">Post Kitchen Issue</div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.kitchen.issues.store') }}" class="row g-3 kitchen-issue-form">
                                @csrf
                                <div class="col-12 col-md-6 col-lg-auto"><input name="issue_date" id="kitchen-issue-date" type="date" class="form-control kitchen-field-date" value="{{ old('issue_date') }}" required></div>
                                <div class="col-12 col-md-6 col-lg-auto">
                                    <select name="mess_id" id="kitchen-mess-select" class="form-select kitchen-field-mess" required>
                                        <option value="">Select mess</option>
                                        @foreach($messes as $mess)
                                            <option value="{{ $mess->id }}" @selected((string) old('mess_id') === (string) $mess->id)>{{ $mess->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-lg">
                                    <select name="item_id" id="kitchen-item-select" class="form-select kitchen-field-item" required>
                                        <option value="">Select stock item</option>
                                    </select>
                                    <div class="small text-muted" id="kitchen-target-status">Only items with current available stock are shown.</div>
                                </div>
                                <div class="col-12 col-sm-6 col-lg-auto"><input name="quantity" id="kitchen-qty-input" type="number" step="0.001" min="0.001" class="form-control kitchen-field-qty" value="{{ old('quantity') }}" required></div>
                                <div class="col-12 col-sm-6 col-lg-auto">
                                    <select name="issue_type" class="form-select kitchen-field-type" required>
                                        <option value="CONSUMPTION" @selected(old('issue_type') === 'CONSUMPTION')>Consumption</option>
                                        <option value="WASTAGE" @selected(old('issue_type') === 'WASTAGE')>Wastage</option>
                                        <option value="DAMAGE" @selected(old('issue_type') === 'DAMAGE')>Damage</option>
                                        <option value="EXPIRED" @selected(old('issue_type') === 'EXPIRED')>Expired</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6 col-lg-auto">
                                    <select name="unit_code" id="kitchen-unit-select" class="form-select kitchen-field-unit">
                                        <option value="">Base unit</option>
                                    </select>
                                    <div class="small text-muted" id="kitchen-conversion-preview"></div>
                                </div>
                                <div class="col-12 col-lg"><input name="remarks" class="form-control kitchen-field-remarks" placeholder="remarks" value="{{ old('remarks') }}"></div>
                                <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary px-4">Post Issue</button></div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="row g-3">
                        <div class="col-sm-6 col-xl-12">
                            <div class="kitchen-mini-stat">
                                <div class="kitchen-mini-stat-label">Issue Rows Loaded</div>
                                <div class="kitchen-mini-stat-value">{{ $issues->count() }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-12">
                            <div class="kitchen-mini-stat">
                                <div class="kitchen-mini-stat-label">Menus Available</div>
                                <div class="kitchen-mini-stat-value">{{ $menus->count() }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-12">
                            <div class="kitchen-mini-stat">
                                <div class="kitchen-mini-stat-label">Items Available</div>
                                <div class="kitchen-mini-stat-value">{{ $items->count() }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade {{ ($activeTab ?? 'issue') === 'ledger' ? 'show active' : '' }} kitchen-tab-pane" id="tab-ledger" role="tabpanel" aria-labelledby="tab-ledger-tab" tabindex="0">
            <div class="kitchen-tab-kicker">Tracking</div>
            <div class="kitchen-tab-title">Kitchen Issues, Month Summary, and Ledger</div>
            <div class="kitchen-tab-subtitle">Issue list keeps remote fields. Month summary and ledger render stock transaction truth for approved kitchen postings only.</div>
            <div class="row g-3 mb-1">
                <div class="col-md-4">
                    <div class="kitchen-mini-stat">
                        <div class="kitchen-mini-stat-label">Total Issues</div>
                        <div class="kitchen-mini-stat-value">{{ $issues->count() }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="kitchen-mini-stat">
                        <div class="kitchen-mini-stat-label">Monthly Buckets</div>
                        <div class="kitchen-mini-stat-value">{{ $kitchenMonthlySummary->count() }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="kitchen-mini-stat">
                        <div class="kitchen-mini-stat-label">Approved Consumption Rows</div>
                        <div class="kitchen-mini-stat-value">{{ $consumption->count() }}</div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <div class="card kitchen-card">
                        <div class="card-header">Kitchen Issues</div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>Date</th><th>Mess</th><th>Item</th><th>Qty</th><th>Type</th><th>Unit</th><th>Status</th><th>Remarks</th><th class="text-end">Action</th></tr></thead>
                                <tbody>
                                @foreach($issues as $i)
                                    <tr>
                                        <td>{{ $i->issue_date }}</td>
                                        <td>{{ $i->mess?->name ?? '-' }}</td>
                                        <td>{{ $items->firstWhere('id', $i->item_id)?->name ?? $i->item_id }}</td>
                                        <td>{{ rtrim(rtrim(number_format((float) $i->quantity, 3, '.', ''), '0'), '.') }}</td>
                                        <td>{{ $i->issue_type ?? 'CONSUMPTION' }}</td>
                                        <td>{{ $i->unit_code ?: ($items->firstWhere('id', $i->item_id)?->uom ?? '-') }}</td>
                                        <td>
                                            @if($i->status === \App\Models\KitchenIssue::STATUS_APPROVED)
                                                <span class="badge text-bg-success">Approved</span>
                                                @if($i->approved_at)
                                                    <div class="small text-muted">{{ $i->approved_at }}</div>
                                                @endif
                                            @else
                                                <span class="badge text-bg-secondary">Draft</span>
                                            @endif
                                        </td>
                                        <td>{{ $i->remarks }}</td>
                                        <td class="text-end">
                                            @if($i->status === \App\Models\KitchenIssue::STATUS_APPROVED)
                                                <span class="text-success small">Approved</span>
                                            @else
                                                <form method="POST" action="{{ route('admin.kitchen.issues.approve.legacy',$i) }}">
                                                    @csrf
                                                    <input type="hidden" name="return_tab" value="ledger">
                                                    <button class="btn btn-sm btn-outline-success">Approve</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-5">
                    <div class="card kitchen-card h-100">
                        <div class="card-header">Kitchen Month Summary</div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>Month</th><th>Ledger Rows</th><th>Total Qty</th><th>Total Amount</th></tr></thead>
                                <tbody>
                                @forelse($kitchenMonthlySummary as $row)
                                    <tr>
                                        <td>{{ $row->month_cycle }}</td>
                                        <td>{{ $row->ledger_rows }}</td>
                                        <td>{{ rtrim(rtrim(number_format((float) $row->total_qty, 3, '.', ''), '0'), '.') }}</td>
                                        <td>{{ number_format((float) $row->total_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted">No monthly ledger rows available.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-7">
                    <div class="card kitchen-card h-100">
                        <div class="card-header">Kitchen Ledger</div>
                        <div class="card-body">
                            <form method="GET" action="{{ route('admin.kitchen.index') }}" class="row g-2 align-items-end mb-3">
                                <input type="hidden" name="tab" value="ledger">
                                <div class="col-12 col-md-3">
                                    <label class="form-label">From Date</label>
                                    <input type="date" name="from_date" value="{{ $fromDate }}" class="form-control">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label">To Date</label>
                                    <input type="date" name="to_date" value="{{ $toDate }}" class="form-control">
                                </div>
                                <div class="col-12 col-md-2 d-grid">
                                    <button class="btn btn-primary">Apply Filter</button>
                                </div>
                                <div class="col-12 col-md-2 d-grid">
                                    <a href="{{ route('admin.kitchen.ledger.export', ['from_date' => $fromDate, 'to_date' => $toDate]) }}" class="btn btn-outline-primary">Download Detailed Consumption</a>
                                </div>
                                <div class="col-12 col-md-2 d-grid">
                                    <a href="{{ route('admin.kitchen.ledger.export-summary', ['from_date' => $fromDate, 'to_date' => $toDate]) }}" class="btn btn-outline-primary">Download Item Summary</a>
                                </div>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead><tr><th>Date</th><th>Mess</th><th>Reference</th><th>Item</th><th>Qty Out</th><th>Type</th><th>Unit</th><th>Remarks</th></tr></thead>
                                    <tbody>
                                    @forelse($kitchenLedgerRows as $row)
                                        <tr>
                                            <td>{{ optional($row->txn_at)->format('Y-m-d H:i') }}</td>
                                            <td>{{ $row->mess_name ?? '-' }}</td>
                                            <td>{{ class_basename($row->reference_type) }} #{{ $row->reference_id }}</td>
                                            <td>{{ $row->item_name }}</td>
                                            <td>{{ rtrim(rtrim(number_format((float) $row->quantity, 3, '.', ''), '0'), '.') }} {{ $row->item_uom }}</td>
                                            <td>{{ $row->issue_type ?? 'CONSUMPTION' }}</td>
                                            <td>{{ $row->item_uom }}</td>
                                            <td>{{ $row->remarks }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="text-muted">No ledger rows available.</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade {{ ($activeTab ?? 'issue') === 'consumption-report' ? 'show active' : '' }} kitchen-tab-pane" id="tab-consumption-report" role="tabpanel" aria-labelledby="tab-consumption-report-tab" tabindex="0">
            <div class="kitchen-tab-kicker">Reporting</div>
            <div class="kitchen-tab-title">Consumption Report</div>
            <div class="kitchen-tab-subtitle">Reporting only, derived from approved kitchen stock transactions.</div>

            <div class="card kitchen-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Item-wise Consumption</span>
                    <a href="{{ route('admin.kitchen.consumption-report.export', ['from_date' => $fromDate, 'to_date' => $toDate, 'item_id' => $consumptionItemId ?? '', 'category' => $consumptionCategory ?? '']) }}" class="btn btn-sm btn-outline-primary">Download CSV</a>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.kitchen.index') }}" class="row g-2 align-items-end mb-3">
                        <input type="hidden" name="tab" value="consumption-report">
                        <div class="col-md-2"><label class="form-label">Month</label><input type="month" name="month" value="{{ $selectedMonth }}" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label">From</label><input type="date" name="from_date" value="{{ $fromDate }}" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label">To</label><input type="date" name="to_date" value="{{ $toDate }}" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Item</label><select name="item_id" class="form-select"><option value="">All</option>@foreach($items as $item)<option value="{{ $item->id }}" @selected((string)($consumptionItemId ?? '') === (string)$item->id)>{{ $item->sku }} - {{ $item->name }}</option>@endforeach</select></div>
                        <div class="col-md-2"><label class="form-label">Category</label><input type="text" name="category" value="{{ $consumptionCategory ?? '' }}" class="form-control"></div>
                        <div class="col-md-1 d-grid"><button class="btn btn-primary">Apply</button></div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Item Code</th><th>Item Name</th><th>Category</th><th>Qty Consumed</th><th>UOM</th><th>Unit Cost</th><th>Total Amount</th><th>Reference</th></tr></thead>
                            <tbody>
                            @forelse(($consumptionReportRows ?? collect()) as $row)
                                @php $avgUnitCost = (float)$row->total_quantity > 0 ? ((float)$row->total_amount / (float)$row->total_quantity) : 0; @endphp
                                <tr>
                                    <td>{{ $row->item_sku }}</td>
                                    <td>{{ $row->item_name }}</td>
                                    <td>{{ $row->item_category }}</td>
                                    <td>{{ number_format((float)$row->total_quantity, 3) }}</td>
                                    <td>{{ $row->item_uom }}</td>
                                    <td>{{ number_format($avgUnitCost, 2) }}</td>
                                    <td>{{ number_format((float)$row->total_amount, 2) }}</td>
                                    <td>KitchenIssue #{{ $row->first_issue_id }} / StockTxn #{{ $row->first_stock_txn_id }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-muted">No consumption rows available.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade {{ ($activeTab ?? 'issue') === 'menu' ? 'show active' : '' }} kitchen-tab-pane" id="tab-menu" role="tabpanel" aria-labelledby="tab-menu-tab" tabindex="0">
            <div class="kitchen-tab-kicker">Recipes</div>
            <div class="kitchen-tab-title">Menu / Recipes</div>
            <div class="kitchen-tab-subtitle">Remote menu and recipe flows preserved inside one tabbed workspace.</div>

            <div class="row g-3">
                <div class="col-12 col-xl-5">
                    @if(auth()->user()->hasPermission('menu.manage'))
                    <div class="card kitchen-card mb-3">
                        <div class="card-header">Create Menu</div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.kitchen.menus.store') }}" class="row g-2">
                                @csrf
                                <div class="col-md-6"><input class="form-control" name="name" placeholder="Menu name" required></div>
                                <div class="col-md-4"><input class="form-control" name="meal_type" placeholder="Meal type" required></div>
                                <div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
                            </form>
                        </div>
                    </div>
                    @endif

                    <div class="card kitchen-card">
                        <div class="card-header">Menus</div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Actions</th></tr></thead>
                                <tbody>
                                @foreach($menus as $m)
                                    <tr>
                                        <td>{{ $m->id }}</td>
                                        <td>{{ $m->name }}</td>
                                        <td>{{ $m->meal_type }}</td>
                                        <td class="d-flex gap-1">
                                            @if(auth()->user()->hasPermission('menu.manage'))
                                            <form method="POST" action="{{ route('admin.kitchen.menus.edit.legacy',$m) }}" class="d-flex gap-1">@csrf
                                                <input type="hidden" name="name" value="{{ $m->name }}">
                                                <input type="hidden" name="meal_type" value="{{ $m->meal_type }}">
                                                <button class="btn btn-sm btn-outline-secondary">Save</button>
                                            </form>
                                            @endif
                                            @if(auth()->user()->hasPermission('menu.approve'))
                                            <form method="POST" action="{{ route('admin.kitchen.menus.delete.legacy',$m) }}">@csrf<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-7">
                    <div class="card kitchen-card mb-3">
                        <div class="card-header">Add Recipe Line</div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.kitchen.recipes.store') }}" class="row g-2">
                                @csrf
                                <div class="col-md-4"><select name="menu_id" class="form-select" required>@foreach($legacyMenuOptions as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select></div>
                                <div class="col-md-4"><select name="item_id" class="form-select" required>@foreach($items as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach</select></div>
                                <div class="col-md-2"><input class="form-control" name="qty_per_serving" step="0.0001" type="number" required></div>
                                <div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
                            </form>
                        </div>
                    </div>

                    <div class="card kitchen-card">
                        <div class="card-header">Recipes List</div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>Menu</th><th>Item</th><th>Qty/Serving</th><th class="text-end">Action</th></tr></thead>
                                <tbody>
                                @foreach($recipes as $r)
                                    <tr>
                                        <td>{{ $legacyMenuOptions->firstWhere('id',$r->menu_id)?->name ?? $r->menu_id }}</td>
                                        <td>{{ $items->firstWhere('id',$r->item_id)?->name ?? $r->item_id }}</td>
                                        <td>{{ $r->qty_per_serving }}</td>
                                        <td class="text-end"><form method="POST" action="{{ route('admin.kitchen.recipes.delete.legacy',$r) }}">@csrf<button class="btn btn-sm btn-outline-danger">Delete</button></form></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade {{ ($activeTab ?? 'issue') === 'plans' ? 'show active' : '' }} kitchen-tab-pane" id="tab-plans" role="tabpanel" aria-labelledby="tab-plans-tab" tabindex="0">
            <div class="kitchen-tab-kicker">Planning</div>
            <div class="kitchen-tab-title">Meal Plans</div>
            <div class="kitchen-tab-subtitle">Remote meal plan flows preserved in the 4 tab kitchen page.</div>

            <div class="row g-3">
                <div class="col-12 col-xl-5">
                    <div class="card kitchen-card">
                        <div class="card-header">Create Meal Plan</div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.kitchen.plans.store') }}" class="row g-2">
                                @csrf
                                <div class="col-md-4"><input name="plan_date" type="date" class="form-control" required></div>
                                <div class="col-md-4"><select name="menu_id" class="form-select" required>@foreach($legacyMenuOptions as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select></div>
                                <div class="col-md-2"><input name="planned_servings" type="number" min="1" class="form-control" required></div>
                                <div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-7">
                    <div class="card kitchen-card">
                        <div class="card-header">Meal Plans</div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>Date</th><th>Menu</th><th>Servings</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                                <tbody>
                                @foreach($plans as $p)
                                    <tr>
                                        <td>{{ $p->plan_date }}</td>
                                        <td>{{ $legacyMenuOptions->firstWhere('id',$p->menu_id)?->name ?? $p->menu_id }}</td>
                                        <td>{{ $p->planned_servings }}</td>
                                        <td>
                                            @if($p->status === \App\Models\MealPlan::STATUS_APPROVED)
                                                <span class="badge text-bg-success">Approved</span>
                                                @if($p->approved_at)
                                                    <div class="small text-muted">{{ $p->approved_at }}</div>
                                                @endif
                                            @else
                                                <span class="badge text-bg-secondary">Draft</span>
                                            @endif
                                        </td>
                                        <td class="text-end d-flex gap-1 justify-content-end">
                                            @if($p->status !== \App\Models\MealPlan::STATUS_APPROVED)
                                                <form method="POST" action="{{ route('admin.kitchen.plans.approve.legacy',$p) }}">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form>
                                            @endif
                                            <form method="POST" action="{{ route('admin.kitchen.plans.edit.legacy',$p) }}">@csrf<input type="hidden" name="plan_date" value="{{ $p->plan_date }}"><input type="hidden" name="menu_id" value="{{ $p->menu_id }}"><input type="hidden" name="planned_servings" value="{{ $p->planned_servings }}"><button class="btn btn-sm btn-outline-secondary">Save</button></form>
                                        </td>
                                    </tr>
                                @endforeach
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
    $kitchenItemsJson = $issueItems->map(function ($i) {
        return [
            'id' => $i->id,
            'name' => $i->name,
            'base_uom' => $i->uom,
            'available_qty' => number_format((float) ($i->available_qty ?? 0), 3, '.', ''),
            'units' => $i->units->map(function ($u) {
                return [
                    'code' => $u->unit_code,
                    'factor' => (float) $u->factor_to_base,
                    'is_default_for_kitchen' => (bool) $u->is_default_for_kitchen,
                    'is_default_for_grn' => (bool) $u->is_default_for_grn,
                ];
            })->values()->all(),
        ];
    })->values()->all();
@endphp

@push('scripts')
<script>
    (() => {
        const kitchenTabParamMap = {
            'tab-issue-tab': 'issue',
            'tab-ledger-tab': 'ledger',
            'tab-consumption-report-tab': 'consumption-report',
            'tab-menu-tab': 'menu',
            'tab-plans-tab': 'plans',
        };

        const items = @json($kitchenItemsJson);
        const oldItemId = @json(old('item_id'));
        const oldUnitCode = @json(old('unit_code'));
        const itemsById = {};

        items.forEach((i) => { itemsById[i.id] = i; });

        const itemSelect = document.getElementById('kitchen-item-select');
        const unitSelect = document.getElementById('kitchen-unit-select');
        const qtyInput = document.getElementById('kitchen-qty-input');
        const preview = document.getElementById('kitchen-conversion-preview');
        const targetStatus = document.getElementById('kitchen-target-status');

        const syncItems = () => {
            if (!itemSelect) {
                return;
            }

            itemSelect.innerHTML = '';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = items.length > 0 ? 'Select item' : 'No stock-available items';
            itemSelect.appendChild(placeholder);

            items.forEach((item) => {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = `${item.name} (available ${Number(item.available_qty || 0).toFixed(3)} ${item.base_uom})`;
                if (String(oldItemId || '') === String(item.id)) {
                    opt.selected = true;
                }
                itemSelect.appendChild(opt);
            });

            if (targetStatus) {
                targetStatus.textContent = items.length > 0
                    ? 'Dropdown shows only items with current available store stock.'
                    : 'No item is currently eligible because no store stock is available.';
            }

            syncUnits();
        };

        const syncUnits = () => {
            const itemId = Number(itemSelect?.value || 0);
            const item = itemsById[itemId];
            if (!unitSelect) {
                return;
            }

            unitSelect.innerHTML = '';

            if (!item) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'Base unit';
                unitSelect.appendChild(opt);
                syncPreview();
                return;
            }

            const units = item.units || [];

            if (units.length === 0) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = item.base_uom ? `Base unit (${item.base_uom})` : 'Base unit';
                unitSelect.appendChild(opt);
            } else {
                const defaultUnit = units.find(u => u.code === oldUnitCode) || units.find(u => u.is_default_for_kitchen) || units.find(u => u.factor === 1) || units[0];
                const baseOpt = document.createElement('option');
                baseOpt.value = '';
                baseOpt.textContent = item.base_uom ? `Base unit (${item.base_uom})` : 'Base unit';
                unitSelect.appendChild(baseOpt);

                units.forEach((u) => {
                    const opt = document.createElement('option');
                    opt.value = u.code;
                    opt.textContent = `${u.code} (x${u.factor.toFixed(3)} ${item.base_uom})`;
                    if (defaultUnit && defaultUnit.code === u.code) {
                        opt.selected = true;
                    }
                    unitSelect.appendChild(opt);
                });
            }

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
            preview.textContent = `${qty.toFixed(3)} ${unit.code} = ${baseQty.toFixed(3)} ${item.base_uom}`;
        };

        itemSelect?.addEventListener('change', syncUnits);
        unitSelect?.addEventListener('change', syncPreview);
        qtyInput?.addEventListener('input', syncPreview);

        document.querySelectorAll('#kitchenTabs [data-bs-toggle="pill"]').forEach((tabButton) => {
            tabButton.addEventListener('shown.bs.tab', (event) => {
                const tabValue = kitchenTabParamMap[event.target.id] || 'issue';
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabValue);
                window.history.replaceState({}, '', url.toString());
            });
        });

        syncItems();
    })();
</script>
@endpush
