@extends('layouts.app')
@section('title', 'Reset Password')
@section('page_title', 'Reset Password')
@section('content')
<div class="hero-panel p-4 mb-4">
    <div class="section-kicker mb-3"><i class="bi bi-shield-check"></i> Credential Reset</div>
    <h4 class="fw-bold mb-2">Reset Password</h4>
    <p class="text-muted mb-0">Securely complete password reset using the issued token.</p>
</div>
<div class="card shadow-sm">
    <div class="card-header">Reset Password</div>
    <div class="card-body">
        <form method="POST" action="{{ route('password-reset.submit') }}" class="row g-3">
            @csrf
            <div class="col-md-12"><label class="form-label fw-semibold">Reset token</label><input class="form-control" name="token" value="{{ $token }}" placeholder="Reset token" required></div>
            <div class="col-md-6"><label class="form-label fw-semibold">New password</label><input class="form-control" type="password" name="password" placeholder="New password" required></div>
            <div class="col-md-6"><label class="form-label fw-semibold">Confirm password</label><input class="form-control" type="password" name="password_confirmation" placeholder="Confirm password" required></div>
            <div class="col-md-3"><button class="btn btn-primary w-100">Reset</button></div>
        </form>
    </div>
</div>
@endsection
