@extends('layouts.app')
@section('title', 'Audit Log')
@section('content')
<div class="card shadow-sm mb-3"><div class="card-body">
<form method="GET" class="row g-2"><div class="col-md-4"><input class="form-control" name="action" value="{{ $action }}" placeholder="action filter"></div><div class="col-md-2"><button class="btn btn-outline-primary">Filter</button></div></form>
</div></div>
<div class="card shadow-sm"><div class="card-body table-responsive">
<table class="table table-sm"><thead><tr><th>ID</th><th>At</th><th>User</th><th>Action</th><th>Entity</th><th>Reason</th></tr></thead><tbody>
@foreach($rows as $r)<tr><td>{{ $r->id }}</td><td>{{ $r->created_at }}</td><td>{{ $r->user->username ?? '-' }}</td><td>{{ $r->action }}</td><td>{{ $r->entity_type }}#{{ $r->entity_id }}</td><td>{{ $r->reason }}</td></tr>@endforeach
</tbody></table>
</div></div>
@endsection
