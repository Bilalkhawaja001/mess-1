@extends('layouts.app')
@section('content')
<div class="container-fluid py-3"><h1>Edit Incidents</h1>
<form method="POST" action="{{ route('admin.fleet.incidents.update', $record) }}">@csrf @method('PUT')
@include('admin.fleet.incidents._form')
</form></div>
@endsection
