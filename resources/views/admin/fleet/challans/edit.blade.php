@extends('layouts.app')
@section('content')
<div class="container-fluid py-3"><h1>Edit Challans</h1>
<form method="POST" action="{{ route('admin.fleet.challans.update', $record) }}">@csrf @method('PUT')
@include('admin.fleet.challans._form')
</form></div>
@endsection
