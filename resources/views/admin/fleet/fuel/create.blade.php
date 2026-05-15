@extends('layouts.app')
@section('content')
<div class="container-fluid py-3"><h1>Create Fuel</h1>
<form method="POST" action="{{ route('admin.fleet.fuel.store') }}">@csrf
@include('admin.fleet.fuel._form')
</form></div>
@endsection
