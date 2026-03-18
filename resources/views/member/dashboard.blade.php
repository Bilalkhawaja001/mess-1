@extends('layouts.app')

@section('title', 'Member Dashboard')
@section('page_title', 'Member Dashboard')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <h5 class="mb-2">Welcome, {{ $user->name ?? $user->username }}</h5>
        <p class="text-muted mb-2">Member payment module enabled (internal architecture mode, no live charging).</p>
        <a class="btn btn-sm btn-primary" href="{{ route('member.payments.index') }}">My Payments</a>
    </div>
</div>
@endsection
