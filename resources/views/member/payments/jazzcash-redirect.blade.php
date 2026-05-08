@extends('layouts.app')

@section('title', 'Redirecting to JazzCash')
@section('page_title', 'Redirecting to JazzCash')

@section('content')
<div class="card shadow-sm">
    <div class="card-body text-center">
        <h5>Redirecting to JazzCash...</h5>
        <p class="text-muted mb-3">Please wait. Do not refresh this page.</p>

        <form id="jazzcashForm" method="POST" action="{{ $postUrl }}">
            @foreach($payload as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
        </form>

        <button class="btn btn-primary" onclick="document.getElementById('jazzcashForm').submit()">Continue to JazzCash</button>
    </div>
</div>

<script>
    setTimeout(function () {
        document.getElementById('jazzcashForm').submit();
    }, 700);
</script>
@endsection
