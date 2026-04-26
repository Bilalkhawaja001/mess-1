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
    <p class="recovery-subtitle">Use the issued token to set a new password.</p>
</div>

@include('partials.flash')

<form method="POST" action="{{ route('password-reset.consume.public') }}" class="row g-3 recovery-form">
    @csrf
    <div class="col-12">
        <label class="form-label fw-semibold">Reset token</label>
        <input class="form-control" name="token" value="{{ old('token', request('token')) }}" placeholder="Reset token" required>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">New password</label>
        <input class="form-control" type="password" name="new_password" placeholder="New password" required>
    </div>
    <div class="col-12">
        <button class="btn btn-primary w-100">Reset Password</button>
    </div>
</form>

<div class="text-center mt-3">
    <a href="{{ route('password-reset.request.form') }}" class="text-decoration-none fw-semibold">Need a token first?</a>
</div>
@endsection
