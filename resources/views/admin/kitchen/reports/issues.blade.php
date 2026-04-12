@extends('layouts.app')

@section('title', 'Approved Kitchen Issue Report')
@section('page_title', 'Approved Kitchen Issue Report')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.kitchen.reports.issues') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" value="{{ $filters['from_date'] }}" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" value="{{ $filters['to_date'] }}" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Item</label>
                <select name="item_id" class="form-select">
                    <option value="">All Items</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}" @selected((string) $filters['item_id'] === (string) $item->id)>
                            {{ $item->sku }} - {{ $item->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary flex-fill">Load</button>
                <a href="{{ route('admin.kitchen.reports.issues.export', request()->query()) }}" class="btn btn-outline-secondary flex-fill">CSV</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Grand Total Estimated Amount</div>
            <div class="fs-4 fw-bold">{{ number_format($totals['grand_total_amount'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Total Unique Items</div>
            <div class="fs-4 fw-bold">{{ $totals['total_unique_items'] }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Total Approved Issue Rows</div>
            <div class="fs-4 fw-bold">{{ $totals['total_approved_issue_rows'] }}</div>
        </div></div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body small text-muted">
        <div><strong>Meaning:</strong> This is an issue / stock-out report based on approved kitchen issues only. It is not an actual consumption or wastage report.</div>
        <div><strong>Rate Source:</strong> {{ $rateSource }}</div>
        <div><strong>Formula:</strong> {{ $rateFormula }}</div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Approved Kitchen Issue Report</div>
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>SKU</th>
                    <th>Item Name</th>
                    <th>UOM</th>
                    <th class="text-end">Total Issued Qty</th>
                    <th class="text-end">Issue Count</th>
                    <th class="text-end">Estimated Avg Rate</th>
                    <th class="text-end">Estimated Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row['item_id'] }}</td>
                        <td>{{ $row['sku'] }}</td>
                        <td>{{ $row['item_name'] }}</td>
                        <td>{{ $row['uom'] }}</td>
                        <td class="text-end">{{ number_format($row['total_issued_qty'], 3) }}</td>
                        <td class="text-end">{{ $row['issue_count'] }}</td>
                        <td class="text-end">{{ number_format($row['avg_rate'], 2) }}</td>
                        <td class="text-end">{{ number_format($row['estimated_amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state mb-0">No approved kitchen issue rows found for the selected filters.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="5">Totals</td>
                    <td class="text-end">{{ $totals['total_approved_issue_rows'] }}</td>
                    <td></td>
                    <td class="text-end">{{ number_format($totals['grand_total_amount'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
