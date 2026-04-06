@extends('layouts.auth')

@section('title', 'Complete Registration - NODESKY')

@section('auth_content')
<div class="section-kicker mb-3"><i class="bi bi-person-check"></i> Account Completion</div>
<h2 class="mb-2 fw-bold">Complete Registration</h2>
<p class="text-muted mb-4">Member: {{ $member->member_code }} - {{ $member->name }}. Finalize your NODESKY account credentials below.</p>

@include('partials.flash')

<form method="POST" action="{{ route('member.register.complete.submit') }}" class="row g-3">
    @csrf
    <div class="col-12">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Confirm Password</label>
        <input type="password" name="password_confirmation" class="form-control" required>
    </div>
    <div class="col-12">
        <button class="btn btn-success w-100">Create Account</button>
    </div>
</form>
@endsection
