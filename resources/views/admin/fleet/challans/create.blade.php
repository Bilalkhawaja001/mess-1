@extends('layouts.app')
@section('content')
<div class="container-fluid py-3"><h1>Create Challans</h1>
<form method="POST" action="{{ route('admin.fleet.challans.store') }}">@csrf
@include('admin.fleet.challans._form')
</form></div>
@endsection
