@extends('layouts.auth')

@section('title', 'Login - Mess Billing')

@push('styles')
<style>
    .login-header {
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .login-kicker {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .login-title {
        font-size: 1.45rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.35rem;
    }

    .login-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    .login-form .form-label {
        font-size: 0.88rem;
        color: #334155;
        margin-bottom: 0.45rem;
    }

    .login-form .form-control {
        min-height: 46px;
        border-radius: 12px;
    }

    .login-form .btn {
        min-height: 46px;
        border-radius: 12px;
        font-weight: 600;
    }

    .login-recovery-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: 0.95rem;
        font-size: 0.9rem;
        font-weight: 600;
        text-decoration: none;
    }
</style>
@endpush

@section('auth_content')
<div class="login-header">
    <div class="login-kicker">Mess Billing Portal</div>
    <h1 class="login-title">Login</h1>
    <p class="login-subtitle">Enter your credentials to continue.</p>
</div>

@include('partials.flash')

<form method="POST" action="{{ route('login.attempt') }}" class="row g-3 login-form">
    @csrf
    <div class="col-12">
        <label class="form-label fw-semibold">Username</label>
        <input type="text" name="username" value="{{ old('username') }}" class="form-control" required autofocus>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <div class="col-12">
        <button class="btn btn-primary w-100" type="submit">Login</button>
    </div>
</form>

<div class="text-center">
    <a href="{{ route('password-reset.request.form') }}" class="login-recovery-link">Forgot password?</a>
</div>
@endsection
