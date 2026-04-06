@extends('layouts.app')
@section('title', 'Password Reset Request')
@section('page_title', 'Password Reset Request')
@section('content')
<div class="hero-panel p-4 mb-4">
    <div class="section-kicker mb-3"><i class="bi bi-key"></i> Access Recovery</div>
    <h4 class="fw-bold mb-2">Request Password Reset</h4>
    <p class="text-muted mb-0">Generate a reset token for internal account recovery without changing system behavior.</p>
</div>
<div class="card shadow-sm">
    <div class="card-header">Generate Reset Token</div>
    <div class="card-body">
        <form method="POST" action="{{ route('password-reset.request') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-lg-8"><label class="form-label fw-semibold">Username</label><input class="form-control" name="username" placeholder="Username" required></div>
            <div class="col-lg-4"><button class="btn btn-primary w-100">Generate Token</button></div>
        </form>
    </div>
</div>
@endsection
