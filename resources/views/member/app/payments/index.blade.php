@extends('layouts.member-app')

@section('title', 'Bills & Payments')
@section('app_title', 'Bills & Payments')

@section('content')
    <section class="app-card">
        <h2>Recent Bills</h2>
        <div class="app-list">
            @forelse($bills as $bill)
                <div class="app-list-item d-flex justify-content-between gap-3">
                    <div><strong>{{ $bill->month_cycle }}</strong><div class="muted">Status: {{ $bill->status ?? '-' }}</div></div>
                    <strong>PKR {{ number_format((float) $bill->net_payable, 2) }}</strong>
                </div>
            @empty
                <p class="muted mb-0">No bills found.</p>
            @endforelse
        </div>
    </section>

    <section class="app-card">
        <h2>Payments</h2>
        <div class="app-list">
            @forelse($payments as $payment)
                <div class="app-list-item d-flex justify-content-between gap-3">
                    <div><strong>{{ optional($payment->created_at)->format('Y-m-d') ?? '-' }}</strong><div class="muted">{{ $payment->status ?? '-' }}</div></div>
                    <strong>PKR {{ number_format((float) $payment->amount, 2) }}</strong>
                </div>
            @empty
                <p class="muted mb-0">No payments found.</p>
            @endforelse
        </div>
    </section>
@endsection
