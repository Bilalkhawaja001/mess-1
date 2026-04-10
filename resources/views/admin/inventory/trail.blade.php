@extends('layouts.app')

@section('title', 'Item Trail')
@section('page_title', 'Procurement to Consumption Trail')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-header">
        <h5 class="mb-0">Item Summary</h5>
    </div>
    <div class="card-body">
        <div><strong>ItemCode:</strong> {{ $item->sku }}</div>
        <div><strong>Name:</strong> {{ $item->name }}</div>
        <div><strong>Base Unit:</strong> {{ $item->uom }}</div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">Procurement (Inward)</div>
            <div class="card-body table-responsive">
                <table class="table table-sm">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Base Qty</th>
                        <th>Trans Qty</th>
                        <th>GRN</th>
                        <th>PO</th>
                        <th>Vendor</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($inward as $row)
                        <tr>
                            <td>{{ $row['txn_at'] }}</td>
                            <td>{{ number_format((float) $row['quantity'], 3) }} {{ $item->uom }}</td>
                            <td>
                                @if($row['trans_quantity'])
                                    {{ number_format((float) $row['trans_quantity'], 3) }} {{ $row['trans_unit_code'] }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $row['grn_number'] ?? '—' }}</td>
                            <td>{{ $row['po_number'] ?? '—' }}</td>
                            <td>{{ $row['vendor_name'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted text-center">No inward transactions</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">Kitchen Issues (Outward)</div>
            <div class="card-body table-responsive">
                <table class="table table-sm">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Base Qty</th>
                        <th>Trans Qty</th>
                        <th>Type</th>
                        <th>Mess</th>
                        <th>Remarks</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($outward as $row)
                        <tr>
                            <td>{{ $row['txn_at'] }}</td>
                            <td>{{ number_format((float) $row['quantity'], 3) }} {{ $item->uom }}</td>
                            <td>
                                @if($row['trans_quantity'])
                                    {{ number_format((float) $row['trans_quantity'], 3) }} {{ $row['trans_unit_code'] }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $row['issue_type'] ?? '—' }}</td>
                            <td>{{ $row['mess_name'] ?? '—' }}</td>
                            <td>{{ $row['remarks'] ?? '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted text-center">No outward transactions</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
