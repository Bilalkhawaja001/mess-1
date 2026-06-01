@extends('layouts.member-app')

@section('title', 'Statement')
@section('app_title', 'Statement')

@section('content')
    <section class="app-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Ledger</h2>
            <span class="app-pill">Balance PKR {{ number_format((float) $outstandingAmount, 2) }}</span>
        </div>
        <div class="app-list">
            @forelse($rows as $row)
                <div class="app-list-item">
                    <div class="d-flex justify-content-between gap-3"><strong>{{ $row->date }}</strong><strong>PKR {{ number_format((float) $row->running_balance, 2) }}</strong></div>
                    <div class="muted">{{ $row->description }}</div>
                    <div class="small mt-2">Debit: {{ number_format((float) $row->debit, 2) }} · Credit: {{ number_format((float) $row->credit, 2) }}</div>
                </div>
            @empty
                <p class="muted mb-0">No statement entries found.</p>
            @endforelse
        </div>
    </section>
@endsection
