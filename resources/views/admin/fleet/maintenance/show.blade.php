@extends('layouts.app')
@section('content')
<div class="container-fluid py-3">
<h1>Maintenance Detail</h1>
<div class="card"><div class="card-body"><pre>{{ json_encode($record->toArray(), JSON_PRETTY_PRINT) }}</pre></div></div>
</div>
@endsection
