@extends('layouts.app')

@section('title', 'Member Dashboard')
@section('page_title', 'Member Dashboard')

@section('content')
<div class="hero-panel p-4 mb-4">
    <div class="section-kicker mb-3"><i class="bi bi-person-workspace"></i> Member Workspace</div>
    <h4 class="mb-2 fw-bold">Welcome, {{ $user->name ?? $user->username }}</h4>
    <p class="text-muted mb-0">Member payment module enabled in architecture mode with the same premium NODESKY surface styling.</p>
</div>

@if($memberProfileMissing)
<div class="alert alert-warning shadow-sm" role="alert">
    Your member profile is not linked yet. Please contact admin.
</div>
@endif

<div class="card shadow-sm">
    <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h5 class="mb-2">Member payments</h5>
            <p class="text-muted mb-0">Review your payment history and account billing activity.</p>
        </div>
        @if($member)
            <a class="btn btn-primary" href="{{ route('member.payments.index') }}">My Payments</a>
        @else
            <span class="btn btn-outline-secondary disabled">My Payments</span>
        @endif
    </div>
</div>
@endsection
