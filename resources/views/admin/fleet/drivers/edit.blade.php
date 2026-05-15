@extends('layouts.app')
@section('content')
@php($record = $driver)
<div class="container-fluid py-3"><h1>Edit Drivers</h1>
<form method="POST" action="{{ route('admin.fleet.drivers.update', $driver) }}">@csrf @method('PUT')
@include('admin.fleet.drivers._form')
</form></div>
@endsection
