@extends('layouts.app')
@section('title', 'Reset Password')
@section('content')
<div class="card shadow-sm">
    <div class="card-header">Reset Password</div>
    <div class="card-body">
        <form method="POST" action="{{ route('password-reset.submit') }}" class="row g-2">
            @csrf
            <div class="col-md-12"><input class="form-control" name="token" value="{{ $token }}" placeholder="Reset token" required></div>
            <div class="col-md-6"><input class="form-control" type="password" name="password" placeholder="New password" required></div>
            <div class="col-md-6"><input class="form-control" type="password" name="password_confirmation" placeholder="Confirm password" required></div>
            <div class="col-md-3"><button class="btn btn-primary">Reset</button></div>
        </form>
    </div>
</div>
@endsection
