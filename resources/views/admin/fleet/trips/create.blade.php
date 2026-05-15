@extends('layouts.app')
@section('content')
<div class="container-fluid py-3"><h1>Create Trips</h1>
<form method="POST" action="{{ route('admin.fleet.trips.store') }}">@csrf
@include('admin.fleet.trips._form')
</form></div>
@endsection
