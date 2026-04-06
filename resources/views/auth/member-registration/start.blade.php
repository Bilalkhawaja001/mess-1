@extends('layouts.auth')

@section('title', 'Member Registration - NODESKY')

@section('auth_content')
<div class="section-kicker mb-3"><i class="bi bi-person-plus"></i> Member Onboarding</div>
<h2 class="mb-2 fw-bold">Register as Member</h2>
<p class="text-muted mb-4">Start your NODESKY member access journey using your member code and registered mobile number.</p>

@include('partials.flash')

<form method="POST" action="{{ route('member.register.start.submit') }}" class="row g-3">
    @csrf
    <div class="col-12">
        <label class="form-label fw-semibold">Member ID</label>
        <input type="text" name="member_id" value="{{ old('member_id') }}" class="form-control" required>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Registered Mobile Number</label>
        <input type="text" name="mobile_number" value="{{ old('mobile_number') }}" class="form-control" required>
    </div>
    <div class="col-12">
        <button class="btn btn-primary w-100">Send OTP</button>
    </div>
</form>
<a href="{{ route('login') }}" class="btn btn-link w-100 mt-3">Back to Login</a>
@endsection
