@extends('layouts.app')
@section('title','Extras')
@section('page_title','Extras')
@section('content')
<div class="card shadow-sm mb-3"><div class="card-header">Add Extra</div><div class="card-body">
<form method="POST" action="{{ route('admin.extras.store') }}" class="row g-2">@csrf
<div class="col-md-3"><input type="date" name="extra_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
<div class="col-md-4"><select name="member_id" class="form-select" required><option value="">Member</option>@foreach($members as $m)<option value="{{ $m->id }}">{{ $m->member_code }} - {{ $m->name }}</option>@endforeach</select></div>
<div class="col-md-3"><input name="description" class="form-control" placeholder="Description" required></div>
<div class="col-md-2"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required></div>
<div class="col-md-2"><button class="btn btn-primary">Add</button></div>
</form></div></div>
<div class="card shadow-sm"><div class="card-header">Recent Extras</div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Member</th><th>Description</th><th>Amount</th></tr></thead><tbody>@foreach($rows as $r)<tr><td>{{ optional($r->extra_date)->format('Y-m-d') }}</td><td>{{ $r->member->member_code ?? '-' }}</td><td>{{ $r->description }}</td><td>{{ number_format((float)$r->amount,2) }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
