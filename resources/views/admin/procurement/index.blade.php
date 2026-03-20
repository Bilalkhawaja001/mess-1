@extends('layouts.app')
@section('title','Procurement')
@section('page_title','Procurement')
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
            <div class="col-12"><select name="item_id" class="form-select" required>@foreach($items as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach</select></div>
            <div class="col-6"><input type="date" name="po_date" class="form-control" required></div>
            <div class="col-6"><input type="number" step="0.001" min="0.001" name="qty_ordered" class="form-control" placeholder="qty" required></div>
            <div class="col-12"><input type="number" step="0.01" min="0" name="unit_price" class="form-control" placeholder="unit price"></div>
            <div class="col-12"><button class="btn btn-primary">Create PO</button></div>
        </form>
    </div></div></div>

    <div class="col-lg-4"><div class="card shadow-sm"><div class="card-header">Create GRN</div><div class="card-body">
        <form method="POST" action="{{ route('admin.procurement.grn.store') }}" class="row g-2">@csrf
            <div class="col-12"><select name="purchase_order_id" class="form-select" required>@foreach($pos as $po)<option value="{{ $po->id }}">{{ $po->po_number }}</option>@endforeach</select></div>
            <div class="col-12"><select name="item_id" class="form-select" required>@foreach($items as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach</select></div>
            <div class="col-6"><input type="date" name="received_date" class="form-control" required></div>
            <div class="col-6"><input type="number" step="0.001" min="0.001" name="qty_received" class="form-control" required></div>
            <div class="col-12"><input type="number" step="0.01" min="0" name="unit_cost" class="form-control" placeholder="unit cost"></div>
            <div class="col-12"><button class="btn btn-primary">Create GRN</button></div>
        </form>
    </div></div></div>

    <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header">Purchase Orders</div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>PO</th><th>Date</th><th>Status</th><th></th></tr></thead><tbody>
        @foreach($pos as $po)
            <tr><td>{{ $po->po_number }}</td><td>{{ $po->po_date }}</td><td>{{ $po->status }}</td><td><form method="POST" action="{{ route('admin.procurement.po.approve',$po) }}">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form></td></tr>
        @endforeach
    </tbody></table></div></div></div>

    <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header">GRNs</div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>GRN</th><th>Date</th><th>PO</th><th></th></tr></thead><tbody>
        @foreach($grns as $grn)
            <tr><td>{{ $grn->grn_number }}</td><td>{{ $grn->received_date }}</td><td>{{ $grn->purchase_order_id }}</td><td><form method="POST" action="{{ route('admin.procurement.grn.approve',$grn) }}">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form></td></tr>
        @endforeach
    </tbody></table></div></div></div>
</div>
@endsection
