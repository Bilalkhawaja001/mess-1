@extends('layouts.app')
@section('content')
@php($record = $fuelLog)
<div class="container-fluid py-3"><h1>Edit Fuel</h1>
<form method="POST" action="{{ route('admin.fleet.fuel.update', $fuelLog) }}">@csrf @method('PUT')
@include('admin.fleet.fuel._form')
</form></div>
@endsection
