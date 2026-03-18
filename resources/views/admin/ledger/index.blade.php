@extends('layouts.app')

@section('title', 'Ledger')
@section('page_title', 'Member Ledger')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-header">Ledger Filters</div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Member</label>
                <select name="member_id" class="form-select">
                    <option value="">All</option>
                    @foreach($members as $m)
                        <option value="{{ $m->id }}" @selected((string)$memberId === (string)$m->id)>{{ $m->member_code }} - {{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label><input type="checkbox" name="include_future" value="1" @checked($includeFuture)> Include Future</label></div>
            <div class="col-md-2"><label><input type="checkbox" name="include_zero" value="1" @checked($includeZero)> Include Zero</label></div>
            <div class="col-md-2"><button class="btn btn-outline-primary">Apply</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header">Post Manual Adjustment</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.ledger.adjustments.store') }}" class="row g-2">
            @csrf
            <div class="col-md-3">
                <select name="member_id" class="form-select" required>
                    <option value="">Member</option>
                    @foreach($members as $m)
                        <option value="{{ $m->id }}">{{ $m->member_code }} - {{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><input type="date" name="entry_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
            <div class="col-md-2"><input type="number" step="0.01" name="debit" class="form-control" placeholder="Debit"></div>
            <div class="col-md-2"><input type="number" step="0.01" name="credit" class="form-control" placeholder="Credit"></div>
            <div class="col-md-2"><input name="reason_code" class="form-control" placeholder="Reason"></div>
            <div class="col-md-1"><button class="btn btn-primary">Post</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Ledger Entries</div>
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Date</th><th>Member</th><th>Ref</th><th>Debit</th><th>Credit</th><th>Balance After</th><th>Reason</th></tr></thead>
            <tbody>
            @foreach($rows as $r)
                <tr>
                    <td>{{ optional($r->entry_date)->format('Y-m-d') }}</td>
                    <td>{{ $r->member->member_code ?? '-' }}</td>
                    <td>{{ $r->ref_type }}#{{ $r->ref_id }}</td>
                    <td>{{ number_format((float)$r->debit,2) }}</td>
                    <td>{{ number_format((float)$r->credit,2) }}</td>
                    <td>{{ number_format((float)$r->balance_after,2) }}</td>
                    <td>{{ $r->reason_code }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
