@extends('layouts.auth')

@section('title', 'Verify OTP - NODESKY')

@section('auth_content')
<div class="section-kicker mb-3"><i class="bi bi-patch-check"></i> OTP Verification</div>
<h2 class="mb-2 fw-bold">Verify OTP</h2>
<p class="text-muted mb-4">OTP sent to {{ $maskedMobile }}. Complete verification to continue your secure member onboarding flow.</p>

@include('partials.flash')

<form method="POST" action="{{ route('member.register.verify.submit') }}" class="row g-3">
    @csrf
    <div class="col-12">
        <label class="form-label fw-semibold">6-digit OTP</label>
        <input type="text" name="otp_code" maxlength="6" class="form-control" required>
    </div>
    <div class="col-12">
        <button class="btn btn-primary w-100">Verify OTP</button>
    </div>
</form>
<form method="POST" action="{{ route('member.register.resend') }}" class="mt-3">
    @csrf
    <button class="btn btn-outline-secondary w-100" @disabled($cooldownSeconds > 0)>Resend OTP @if($cooldownSeconds > 0) ({{ $cooldownSeconds }}s) @endif</button>
</form>
@endsection
