@extends('layouts.auth')

@section('title', 'Reset Password - Mess Billing')

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
    <h1 class="recovery-title">Reset Password</h1>
    <p class="recovery-subtitle">Set a new password for your account.</p>
</div>

@include('partials.flash')

<form method="POST" action="{{ route('password-reset.consume') }}" class="row g-3 recovery-form">
    @csrf
    <input type="hidden" name="token" value="{{ old('token', request('token')) }}">
    <div class="col-12">
        <label class="form-label fw-semibold">New password</label>
        <input class="form-control" type="password" name="new_password" placeholder="New password" required>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Confirm new password</label>
        <input class="form-control" type="password" name="new_password_confirmation" placeholder="Confirm new password" required>
    </div>
    <div class="col-12">
        <button class="btn btn-primary w-100">Reset Password</button>
    </div>
</form>

<div class="text-center mt-3">
    <a href="{{ route('password-reset.request.form') }}" class="text-decoration-none fw-semibold">Request another reset link</a>
</div>
@endsection
