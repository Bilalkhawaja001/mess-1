@extends('layouts.member-app')

@section('title', 'Complaints')
@section('app_title', 'Complaints')

@section('content')
    <section class="app-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">My Complaints</h2>
            <a class="btn btn-sm btn-primary" href="{{ route('member.app.complaints.create') }}">New</a>
        </div>
        <div class="app-list">
            @forelse($rows as $complaint)
                <a class="app-list-item text-decoration-none text-reset" href="{{ route('member.app.complaints.show', $complaint) }}">
                    <span class="app-pill">{{ $complaint->status }}</span>
                    <div class="fw-semibold mt-2">{{ $complaint->subject }}</div>
                    <div class="muted small">{{ optional($complaint->created_at)->format('Y-m-d') ?? '-' }}</div>
                </a>
            @empty
                <p class="muted mb-0">No complaints found.</p>
            @endforelse
        </div>
    </section>
@endsection
