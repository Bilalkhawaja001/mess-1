@extends('layouts.app')
@section('content')
<div class="container-fluid py-3"><h1>Create Documents</h1>
<form method="POST" action="{{ route('admin.fleet.documents.store') }}">@csrf
@include('admin.fleet.documents._form')
</form></div>
@endsection
