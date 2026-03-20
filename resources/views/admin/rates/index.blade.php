@extends('layouts.app')
@section('title','Rates')
@section('page_title','Rate Policies')
@section('content')
<div class="card shadow-sm mb-3"><div class="card-header">Add Rate Policy</div><div class="card-body">
<form method="POST" action="{{ route('admin.rates.store') }}" class="row g-2">@csrf
<div class="col-md-2"><input name="rate_type" class="form-control" placeholder="PER_DAY" required></div>
<div class="col-md-2"><input type="number" step="0.0001" name="value" class="form-control" required></div>
<div class="col-md-2"><input type="date" name="effective_from" class="form-control" required></div>
<div class="col-md-2"><input type="date" name="effective_to" class="form-control"></div>
<div class="col-md-2 form-check mt-2"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked> <label class="form-check-label">Active</label></div>
<div class="col-md-2"><button class="btn btn-primary">Add</button></div>
</form></div></div>
<div class="card shadow-sm mb-3"><div class="card-header">Bulk Import Rates</div><div class="card-body">
<form method="POST" action="{{ route('admin.rates.import') }}" enctype="multipart/form-data" class="row g-2">@csrf
<div class="col-md-6"><input type="file" name="file" class="form-control" accept=".csv,.txt" required></div>
<div class="col-md-3"><button class="btn btn-outline-primary">Import Rates CSV</button></div>
<div class="col-12 text-muted small">Headers: rate_type,value,effective_from,effective_to,is_active</div>
</form></div></div>
<div class="card shadow-sm"><div class="card-header">Rate List</div><div class="card-body table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Type</th><th>Value</th><th>Window</th><th>Active</th><th>Approved</th><th>Actions</th></tr></thead><tbody>@foreach($rows as $r)<tr><td>{{ $r->rate_type }}</td><td>{{ $r->value }}</td><td>{{ optional($r->effective_from)->format('Y-m-d') }} to {{ optional($r->effective_to)->format('Y-m-d') ?: 'Open' }}</td><td>{{ $r->is_active ? 'Yes' : 'No' }}</td><td>{{ $r->approved_at ? 'Yes' : 'No' }}</td><td class="d-flex gap-2"><form method="POST" action="{{ route('admin.rates.toggle-approve',$r->id) }}">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form><form method="POST" action="{{ route('admin.rates.toggle-active',$r->id) }}">@csrf<button class="btn btn-sm btn-outline-warning">Toggle Active</button></form></td></tr>@endforeach</tbody></table></div></div>
@endsection
