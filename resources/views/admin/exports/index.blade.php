@extends('layouts.app')
@section('title','Export Center')
@section('page_title','Export Center')
@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-header">Finance Export Filters</div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Member</label>
                <select name="member_id" class="form-select">
                    <option value="">All</option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}" @selected((string) $memberId === (string) $member->id)>{{ $member->member_code }} - {{ $member->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Month</label>
                <input type="month" name="month_cycle" class="form-control" value="{{ $monthCycle }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
            </div>
        </form>
    </div>
</div>

@php
    $filterQuery = array_filter([
        'member_id' => $memberId,
        'month_cycle' => $monthCycle,
        'from_date' => $fromDate,
        'to_date' => $toDate,
    ], fn ($value) => $value !== null && $value !== '');
@endphp

<div class="card shadow-sm p-3">
    <h5 class="mb-3">Downloads / Export Center</h5>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.exports.bills', $filterQuery) }}">Bills CSV</a>
        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.exports.payments', $filterQuery) }}">Payments CSV</a>
        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.exports.member-ledger', $filterQuery) }}">Member Ledger CSV</a>
        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.exports.statement', $filterQuery) }}">Statement CSV</a>
        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.exports.stock-ledger', $filterQuery) }}">Stock Ledger CSV</a>
        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.exports.guest-meals', $filterQuery) }}">Guest Meals CSV</a>
        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.exports.department-ledger', $filterQuery) }}">Department Ledger CSV</a>
    </div>
</div>
@endsection
