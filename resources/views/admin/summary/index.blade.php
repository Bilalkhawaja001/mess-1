@extends('layouts.app')
@section('title','Summary')
@section('page_title','Monthly Summary')
@section('content')
<div class="card shadow-sm mb-3"><div class="card-body"><form method="GET" class="row g-2 align-items-end"><div class="col-md-3"><label class="form-label">Month (YYYY-MM)</label><input name="month_cycle" value="{{ $monthCycle }}" class="form-control" placeholder="2026-03"></div><div class="col-md-2"><button class="btn btn-outline-primary">Load</button></div>@if($monthCycle)<div class="col-md-2"><a class="btn btn-outline-success" href="{{ route('admin.summary.index',['month_cycle'=>$monthCycle,'export'=>'csv']) }}">Export CSV</a></div>@endif</form></div></div>
<div class="card shadow-sm"><div class="card-header">Summary Records</div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>Member</th><th>Net Payable</th></tr></thead><tbody>@foreach($records as $r)<tr><td>{{ $r->member->member_code ?? '-' }} - {{ $r->member->name ?? '-' }}</td><td>{{ number_format((float)$r->net_payable,2) }}</td></tr>@endforeach</tbody><tfoot><tr><th>Total</th><th>{{ number_format((float)$total,2) }}</th></tr></tfoot></table></div></div>
@endsection
