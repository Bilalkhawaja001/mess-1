@extends('layouts.auth')

@section('title', 'Login - Mess Billing')

@push('styles')
<style>
    .login-header {
        text-align: center;
        margin-bottom: 1rem;
    }

    .login-kicker {
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.14em;
        color: rgba(191, 219, 254, .74);
        font-weight: 700;
        margin-bottom: 0.45rem;
    }

    .login-title {
        font-size: 1.2rem;
        font-weight: 800;
        color: #f8fbff;
        margin-bottom: 0.3rem;
    }

    .login-subtitle {
        color: rgba(191, 219, 254, .72);
        font-size: 0.82rem;
        margin-bottom: 0;
    }

    .login-form .form-label {
        font-size: 0.8rem;
        color: rgba(191, 219, 254, .86);
        margin-bottom: 0.4rem;
    }

    .login-form .form-control {
        min-height: 42px;
        border-radius: 12px;
    }

    .login-form .btn {
        min-height: 42px;
        border-radius: 14px;
        font-weight: 700;
    }

    .login-recovery-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: 0.8rem;
        font-size: 0.82rem;
        font-weight: 600;
        text-decoration: none;
        color: #7dd3fc;
    }

    @media (max-width: 575.98px) {
        .login-header {
            margin-bottom: 0.9rem;
        }

        .login-title {
            font-size: 1.08rem;
        }

        .login-subtitle {
            font-size: 0.78rem;
        }

        .login-form .form-control,
        .login-form .btn {
            min-height: 40px;
        }
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
