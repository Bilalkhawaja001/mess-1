@extends('layouts.app')
@section('content')
<div class="container-fluid py-3"><h1>Edit Trips</h1>
<form method="POST" action="{{ route('admin.fleet.trips.update', $record) }}">@csrf @method('PUT')
@include('admin.fleet.trips._form')
</form></div>
@endsection
