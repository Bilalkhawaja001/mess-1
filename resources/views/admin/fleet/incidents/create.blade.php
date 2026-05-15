@extends('layouts.app')
@section('content')
<div class="container-fluid py-3"><h1>Create Incidents</h1>
<form method="POST" action="{{ route('admin.fleet.incidents.store') }}">@csrf
@include('admin.fleet.incidents._form')
</form></div>
@endsection
