@extends('layouts.app')
@section('title', 'Change Password')
@section('content')
<div class="card shadow-sm">
    <div class="card-header">Change Password</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.auth.password-change') }}" class="row g-2">
            @csrf
            <div class="col-md-12"><input class="form-control" type="password" name="current_password" placeholder="Current password" required></div>
            <div class="col-md-12"><input class="form-control" type="password" name="new_password" placeholder="New password" required></div>
            <div class="col-md-3"><button class="btn btn-primary">Change</button></div>
        </form>
    </div>
</div>
@endsection
