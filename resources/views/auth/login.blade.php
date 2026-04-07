@extends('layouts.auth')

@section('title', 'Login - Mess Billing')

@section('auth_content')
<div class="d-flex justify-content-between align-items-start gap-3 mb-4 flex-wrap">
    <div>
        <div class="section-kicker mb-3"><i class="bi bi-shield-lock"></i> Secure Access</div>
        <h2 class="mb-2 fw-bold">Sign in to Mess Billing</h2>
        <p class="text-muted mb-0">Use your enterprise credentials to access operations, finance, and member workflows.</p>
    </div>
    <span class="badge text-bg-light px-3 py-2">Member Access</span>
</div>

@include('partials.flash')

<div class="auth-actions d-flex flex-wrap gap-2 mb-4">
    <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
    <a href="{{ route('member.register.start') }}" class="btn btn-outline-success">Register as Member</a>
    <a href="#forgot-password" class="btn btn-outline-secondary">Forgot Password</a>
</div>

<form method="POST" action="{{ route('login.attempt') }}" class="row g-3">
    @csrf
    <div class="col-12">
        <label class="form-label fw-semibold">Username</label>
        <input type="text" name="username" value="{{ old('username') }}" class="form-control" required>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <div class="col-12">
        <button class="btn btn-primary w-100">Login</button>
    </div>
</form>

<div class="auth-help mt-4" id="forgot-password">
    <div class="fw-semibold mb-1">Credential recovery</div>
    <p class="small text-muted mb-3">Request a reset token, then complete the password reset below.</p>
    <form method="POST" action="{{ route('password-reset.request.public') }}" class="mb-3">
        @csrf
        <label class="form-label small fw-semibold">Forgot Password (request token)</label>
        <div class="input-group">
            <input type="text" name="username" class="form-control" placeholder="Username" required>
            <button class="btn btn-outline-secondary">Request</button>
        </div>
    </form>
    <form method="POST" action="{{ route('password-reset.consume.public') }}" class="row g-3">
        @csrf
        <div class="col-12"><input type="text" name="token" class="form-control" placeholder="Reset token" required></div>
        <div class="col-12"><input type="password" name="new_password" class="form-control" placeholder="New password" required></div>
        <div class="col-12"><button class="btn btn-outline-dark w-100">Reset Password</button></div>
    </form>
</div>
@endsection
