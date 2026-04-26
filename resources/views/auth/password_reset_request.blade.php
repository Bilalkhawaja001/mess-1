@extends('layouts.auth')

@section('title', 'Password Recovery - Mess Billing')

@push('styles')
<style>
    .recovery-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.35rem;
    }

    .recovery-subtitle {
        color: #64748b;
        margin-bottom: 1.25rem;
    }

    .recovery-form .form-control,
    .recovery-form .btn {
        min-height: 46px;
        border-radius: 12px;
    }
</style>
@endpush

@section('auth_content')
<div class="text-center mb-4">
    <div class="small text-uppercase text-muted fw-semibold mb-2" style="letter-spacing:0.12em;">Mess Billing Portal</div>
    <h1 class="recovery-title">Forgot Password</h1>
    <p class="recovery-subtitle">Enter your username to generate a real password reset token.</p>
</div>

@include('partials.flash')

@if(session('reset_token'))
    <div class="alert alert-success">
        <div class="fw-semibold mb-1">Reset token issued</div>
        <div class="small">{{ session('reset_token') }}</div>
        <a href="{{ route('password-reset.consume.form') }}" class="btn btn-sm btn-outline-success mt-3">Continue to reset password</a>
    </div>
@endif

<form method="POST" action="{{ route('password-reset.request.public') }}" class="row g-3 recovery-form">
    @csrf
    <div class="col-12">
        <label class="form-label fw-semibold">Username</label>
        <input class="form-control" name="username" value="{{ old('username') }}" placeholder="Username" required>
    </div>
    <div class="col-12">
        <button class="btn btn-primary w-100">Generate Reset Token</button>
    </div>
</form>

<div class="text-center mt-3">
    <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Back to login</a>
</div>
@endsection
