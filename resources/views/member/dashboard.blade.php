@extends('layouts.app')

@section('title', 'Member Dashboard')
@section('page_title', 'Member Dashboard')

@section('content')
<div class="hero-panel p-4 mb-4">
    <div class="section-kicker mb-3"><i class="bi bi-person-workspace"></i> Member Workspace</div>
    <h4 class="mb-2 fw-bold">Welcome, {{ $user->name ?? $user->username }}</h4>
    <p class="text-muted mb-0">Your account view is limited to your own mess profile only.</p>
</div>

@if($memberProfileMissing)
<div class="alert alert-warning shadow-sm" role="alert">
    Your member profile is not linked yet. Please contact admin.
</div>
@endif

@if($member)
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="text-muted small mb-1">Total Outstanding Amount</div>
        <div class="fs-2 fw-bold">{{ number_format($outstandingAmount, 2) }}</div>
        <div class="text-muted">{{ $member->member_code }} - {{ $member->name }} @if(!empty($member->department_name)) - {{ $member->department_name }} @endif</div>
    </div>
</div>
@endif

<div class="row g-3">
    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm h-100"><div class="card-body d-flex flex-column gap-2"><h5 class="mb-0">My Statement</h5><p class="text-muted mb-0">View your own ledger only.</p>@if($member)<a class="btn btn-primary mt-auto" href="{{ route('member.statement.index') }}">Open Statement</a>@else<span class="btn btn-outline-secondary disabled mt-auto">Open Statement</span>@endif</div></div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm h-100"><div class="card-body d-flex flex-column gap-2"><h5 class="mb-0">Menu</h5><p class="text-muted mb-0">Read-only weekly mess menu.</p><a class="btn btn-outline-primary mt-auto" href="{{ route('member.menu.index') }}">View Menu</a></div></div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm h-100"><div class="card-body d-flex flex-column gap-2"><h5 class="mb-0">Complaint / Suggestion</h5><p class="text-muted mb-0">Submit and track your own requests.</p><a class="btn btn-outline-primary mt-auto" href="{{ route('member.complaints.index') }}">Open Complaints</a></div></div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm h-100"><div class="card-body d-flex flex-column gap-2"><h5 class="mb-0">My Payments</h5><p class="text-muted mb-0">Existing payment module left untouched.</p>@if($member)<a class="btn btn-outline-primary mt-auto" href="{{ route('member.payments.index') }}">My Payments</a>@else<span class="btn btn-outline-secondary disabled mt-auto">My Payments</span>@endif</div></div>
    </div>
</div>
@endsection
