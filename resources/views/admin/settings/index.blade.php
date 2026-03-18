@extends('layouts.app')
@section('title','Settings')
@section('page_title','App Settings')
@section('content')
<div class="card shadow-sm mb-3"><div class="card-header">Save Setting</div><div class="card-body">
<form method="POST" action="{{ route('admin.settings.store') }}" class="row g-2">@csrf
<div class="col-md-3"><input name="setting_key" class="form-control" placeholder="setting_key" required></div>
<div class="col-md-4"><input name="setting_value" class="form-control" placeholder="value"></div>
<div class="col-md-2"><select name="value_type" class="form-select"><option>string</option><option>int</option><option>float</option><option>bool</option><option>json</option></select></div>
<div class="col-md-2 form-check mt-2"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked> <label class="form-check-label">Active</label></div>
<div class="col-md-1"><button class="btn btn-primary">Save</button></div>
</form></div></div>
<div class="card shadow-sm"><div class="card-header">Settings List</div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>Key</th><th>Value</th><th>Type</th><th>Active</th><th>Action</th></tr></thead><tbody>@foreach($rows as $r)<tr><td>{{ $r->setting_key }}</td><td>{{ $r->setting_value }}</td><td>{{ $r->value_type }}</td><td>{{ $r->is_active ? 'Yes' : 'No' }}</td><td><form method="POST" action="{{ route('admin.settings.toggle',$r->id) }}">@csrf<button class="btn btn-sm btn-outline-warning">Toggle</button></form></td></tr>@endforeach</tbody></table></div></div>
@endsection
