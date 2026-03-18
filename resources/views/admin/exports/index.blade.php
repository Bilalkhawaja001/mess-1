@extends('layouts.app')
@section('title','Export Center')
@section('content')<div class='card p-3'><h5>Downloads / Export Center</h5><div class='d-flex gap-2'><a class='btn btn-sm btn-outline-primary' href='{{ route('admin.exports.stock-ledger') }}'>Stock Ledger CSV</a><a class='btn btn-sm btn-outline-primary' href='{{ route('admin.exports.guest-meals') }}'>Guest Meals CSV</a><a class='btn btn-sm btn-outline-primary' href='{{ route('admin.exports.department-ledger') }}'>Department Ledger CSV</a></div></div>@endsection
