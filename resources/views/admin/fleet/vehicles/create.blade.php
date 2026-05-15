@extends('layouts.app')
@section('content')
<div class="container-fluid py-3"><h1>Create Vehicles</h1>
<form method="POST" action="{{ route('admin.fleet.vehicles.store') }}">@csrf
@include('admin.fleet.vehicles._form')
</form></div>
@endsection
