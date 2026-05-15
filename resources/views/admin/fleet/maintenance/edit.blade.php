@extends('layouts.app')
@section('content')
@php($record = $maintenance)
<div class="container-fluid py-3"><h1>Edit Maintenance</h1>
<form method="POST" action="{{ route('admin.fleet.maintenance.update', $maintenance) }}">@csrf @method('PUT')
@include('admin.fleet.maintenance._form')
</form></div>
@endsection
