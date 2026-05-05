@extends('layouts.app')
@section('title', 'Month Governance')
@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-header">Month Close/Reopen/Hard Reset</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.month.close') }}" class="row g-2 mb-2">@csrf
            <div class="col-md-2"><input class="form-control" name="month_cycle" placeholder="YYYY-MM" required></div>
            <div class="col-md-6"><input class="form-control" name="reason" placeholder="Reason" required></div>
            <div class="col-md-2"><button class="btn btn-warning">Close</button></div>
        </form>
        <form method="POST" action="{{ route('admin.month.reopen') }}" class="row g-2 mb-2">@csrf
            <div class="col-md-2"><input class="form-control" name="month_cycle" placeholder="YYYY-MM" required></div>
            <div class="col-md-6"><input class="form-control" name="reason" placeholder="Reason" required></div>
            <div class="col-md-2"><button class="btn btn-info">Reopen</button></div>
        </form>
        <form method="POST" action="{{ route('admin.month.hard-reset') }}" class="row g-2">@csrf
            <div class="col-md-2"><input class="form-control" name="month_cycle" placeholder="YYYY-MM" required></div>
            <div class="col-md-3"><input class="form-control" name="confirm_text" placeholder="RESET-2026-01" required></div>
            <div class="col-md-5"><input class="form-control" name="reason" placeholder="Reason" required></div>
            <div class="col-md-2"><button class="btn btn-danger">Hard Reset</button></div>
        </form>
    </div>
</div>
<div class="card shadow-sm"><div class="card-body table-responsive">
<table class="table table-sm"><thead><tr><th>Month</th><th>Status</th><th>Reason</th><th>Updated</th></tr></thead><tbody>
@foreach($rows as $r)<tr><td>{{ $r->month_cycle }}</td><td>{{ $r->status }}</td><td>{{ $r->reason }}</td><td>{{ $r->updated_at }}</td></tr>@endforeach
</tbody></table>
</div></div>
@endsection
