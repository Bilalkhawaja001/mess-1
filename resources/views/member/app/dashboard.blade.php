@extends('layouts.member-app')

@section('title', 'Member App Dashboard')
@section('app_title', 'Dashboard')

@section('content')
    @if($memberProfileMissing ?? false)
        <section class="app-card"><h2>Profile not linked</h2><p class="muted mb-0">Your member profile is not linked yet. Please contact admin.</p></section>
    @endif

    <section class="metric-grid mb-3">
        <div class="metric"><span>Outstanding</span><strong>PKR {{ number_format((float) $outstandingAmount, 2) }}</strong></div>
        <div class="metric"><span>Current Bill</span><strong>PKR {{ number_format((float) $currentMonthBill, 2) }}</strong></div>
        <div class="metric"><span>Open Complaints</span><strong>{{ (int) $openComplaintsCount }}</strong></div>
        <div class="metric"><span>Last Payment</span><strong>{{ $lastPayment ? 'PKR '.number_format((float) $lastPayment->amount, 2) : '-' }}</strong></div>
    </section>

    <section class="app-card">
        <h2>Today Menu</h2>
        <div class="app-list">
            @foreach($todayMenu as $meal => $text)
                <div class="app-list-item"><strong>{{ str_replace('_', ' / ', $meal) }}</strong><div class="muted mt-1" style="white-space:pre-line">{{ $text ?: '-' }}</div></div>
            @endforeach
        </div>
    </section>

    <section class="app-card">
        <h2>Recent Complaints</h2>
        @forelse($recentComplaints as $complaint)
            <div class="app-list-item mb-2"><span class="app-pill">{{ $complaint->status }}</span><div class="fw-semibold mt-2">{{ $complaint->subject }}</div></div>
        @empty
            <p class="muted mb-0">No recent complaints.</p>
        @endforelse
    </section>
@endsection
