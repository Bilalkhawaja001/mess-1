@extends('layouts.app')
@section('content')
@php($record = $vehicle)
<div class="container-fluid py-3"><h1>Edit Vehicles</h1>
<form method="POST" action="{{ route('admin.fleet.vehicles.update', $vehicle) }}">@csrf @method('PUT')
@include('admin.fleet.vehicles._form')
</form></div>
@endsection
