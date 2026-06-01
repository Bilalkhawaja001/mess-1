@extends('layouts.member-app')

@section('title', 'Complaint Detail')
@section('app_title', 'Complaint Detail')

@section('content')
    <section class="app-card">
        <span class="app-pill">{{ $complaint->status }}</span>
        <h2 class="mt-3">{{ $complaint->subject }}</h2>
        <p class="muted">{{ $complaint->category }} · {{ $complaint->priority }}</p>
        <div style="white-space:pre-line">{{ $complaint->message ?? $complaint->description }}</div>
    </section>
@endsection
