@extends('layouts.app')
@section('title', 'Password Reset Request')
@section('content')
<div class="card shadow-sm">
    <div class="card-header">Request Password Reset</div>
    <div class="card-body">
        <form method="POST" action="{{ route('password-reset.request') }}" class="row g-2">
            @csrf
            <div class="col-md-4"><input class="form-control" name="username" placeholder="Username" required></div>
            <div class="col-md-2"><button class="btn btn-primary">Generate Token</button></div>
        </form>
    </div>
</div>
@endsection
