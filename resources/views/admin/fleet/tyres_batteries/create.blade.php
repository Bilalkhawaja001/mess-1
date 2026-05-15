@extends('layouts.app')
@section('content')
<div class="container-fluid py-3"><h1>Create Tyres Batteries</h1>
<form method="POST" action="{{ route('admin.fleet.tyres-batteries.store') }}">@csrf
@include('admin.fleet.tyres_batteries._form')
</form></div>
@endsection
