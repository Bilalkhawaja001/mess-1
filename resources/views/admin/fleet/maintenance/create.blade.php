@extends('layouts.app')
@section('content')
<div class="container-fluid py-3"><h1>Create Maintenance</h1>
<form method="POST" action="{{ route('admin.fleet.maintenance.store') }}">@csrf
@include('admin.fleet.maintenance._form')
</form></div>
@endsection
