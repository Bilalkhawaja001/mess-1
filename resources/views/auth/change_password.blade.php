@extends('layouts.app')
@section('title', 'Change Password')
@section('page_title', 'Change Password')
@section('content')
<div class="hero-panel p-4 mb-4">
    <div class="section-kicker mb-3"><i class="bi bi-lock"></i> Security Settings</div>
    <h4 class="fw-bold mb-2">Change Password</h4>
    <p class="text-muted mb-0">Update your current credentials from the NODESKY security panel.</p>
</div>
<div class="card shadow-sm">
    <div class="card-header">Change Password</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.auth.password-change') }}" class="row g-3">
            @csrf
            <div class="col-md-12"><label class="form-label fw-semibold">Current password</label><input class="form-control" type="password" name="current_password" placeholder="Current password" required></div>
            <div class="col-md-12"><label class="form-label fw-semibold">New password</label><input class="form-control" type="password" name="new_password" placeholder="New password" required></div>
            <div class="col-md-3"><button class="btn btn-primary w-100">Change</button></div>
        </form>
    </div>
</div>
@endsection
