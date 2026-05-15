@extends('layouts.app')
@section('content')
<div class="container-fluid py-3"><h1>Create Drivers</h1>
<form method="POST" action="{{ route('admin.fleet.drivers.store') }}">@csrf
@include('admin.fleet.drivers._form')
</form></div>
@endsection
