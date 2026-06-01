@extends('layouts.member-app')

@section('title', 'Profile')
@section('app_title', 'Profile')

@section('content')
    <section class="app-card">
        <h2>{{ $user->name ?? 'Member' }}</h2>
        <p class="muted mb-1">{{ $user->email ?? '-' }}</p>
        <p class="muted mb-0">{{ $member->mobile_number ?? '-' }}</p>
    </section>

    <section class="app-card">
        <h2>Change Requests</h2>
        <div class="app-list">
            @forelse($changeRequests as $request)
                <div class="app-list-item"><span class="app-pill">{{ $request->status }}</span><div class="mt-2">{{ $request->field_name }} → {{ $request->new_value }}</div></div>
            @empty
                <p class="muted mb-0">No change requests.</p>
            @endforelse
        </div>
    </section>
@endsection
