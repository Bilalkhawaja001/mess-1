@extends('layouts.app')
@section('title','Monthly Attendance')
@section('page_title','Monthly Attendance')
@section('content')
<div class="card shadow-sm mb-3"><div class="card-body">
<form method="GET" class="row g-2 align-items-end">
<div class="col-md-3"><label class="form-label">Month Cycle</label><input name="month_cycle" value="{{ $monthCycle }}" class="form-control" placeholder="YYYY-MM"></div>
<div class="col-md-2"><button class="btn btn-outline-primary">Load</button></div>
</form></div></div>

<div class="card shadow-sm"><div class="card-header">Monthly Present Days</div><div class="card-body table-responsive">
<form method="POST" action="{{ route('admin.attendance-monthly.store') }}">@csrf
<input type="hidden" name="month_cycle" value="{{ $monthCycle }}">
<table class="table table-sm align-middle"><thead><tr><th>Member</th><th>Present Days</th><th>Locked</th></tr></thead><tbody>
@foreach($rows as $i => $r)
<tr>
<td>{{ $r['member']->member_code }} - {{ $r['member']->name }}<input type="hidden" name="rows[{{ $i }}][member_id]" value="{{ $r['member']->id }}"></td>
<td><input type="number" min="0" max="31" class="form-control form-control-sm" name="rows[{{ $i }}][present_days]" value="{{ $r['present_days'] }}"></td>
<td>{{ $r['is_locked'] ? 'Yes' : 'No' }}</td>
</tr>
@endforeach
</tbody></table>
<div class="d-flex gap-2"><button class="btn btn-primary">Save</button><button name="approve" value="1" class="btn btn-success">Save & Approve/Lock</button></div>
</form></div></div>
@endsection
