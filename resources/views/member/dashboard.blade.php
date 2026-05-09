@extends('layouts.app')

@section('title', 'Member Dashboard')
@section('page_title', 'Member Dashboard')

@section('content')
@php
    $displayName = $user->name ?? $user->username;
    $dueDays = max(1, now()->diffInDays(now()->addDays(5), false));
@endphp

<div class="member-dashboard-screen">
    @if($memberProfileMissing)
        <div class="alert alert-warning shadow-sm member-alert-card" role="alert">
            Your member profile is not linked yet. Please contact admin.
        </div>
    @endif

    <section class="member-holo-card member-balance-card mb-4">
        <div class="member-balance-card__glow"></div>
        <div class="member-balance-card__content">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <div class="member-balance-card__label">Outstanding Balance</div>
                    <div class="member-balance-card__amount">PKR {{ number_format($outstandingAmount, 2) }}</div>
                </div>
                <div class="member-balance-card__icon"><i class="bi bi-wallet2"></i></div>
            </div>
            <div class="member-balance-card__meta">
                <div>
                    <div class="member-balance-card__label">Due Date</div>
                    <div class="member-balance-card__date">{{ now()->addDays(5)->format('d M Y') }}</div>
                </div>
                <div class="member-due-badge">Due in {{ $dueDays }} days</div>
            </div>
        </div>
    </section>

    <section class="mb-4">
        <div class="member-section-title">Quick Actions</div>
        <div class="member-quick-actions">
            <a class="member-holo-card member-quick-action" href="{{ route('member.statement.index') }}">
                <span class="member-quick-action__icon"><i class="bi bi-journal-text"></i></span>
                <span class="member-quick-action__label">Statement</span>
            </a>
            <a class="member-holo-card member-quick-action" href="{{ route('member.payments.index') }}">
                <span class="member-quick-action__icon"><i class="bi bi-credit-card-2-front"></i></span>
                <span class="member-quick-action__label">Payment</span>
            </a>
            <a class="member-holo-card member-quick-action" href="{{ route('member.complaints.index') }}">
                <span class="member-quick-action__icon"><i class="bi bi-headset"></i></span>
                <span class="member-quick-action__label">Complaint</span>
            </a>
            <a class="member-holo-card member-quick-action" href="{{ route('member.profile.index') }}">
                <span class="member-quick-action__icon"><i class="bi bi-person"></i></span>
                <span class="member-quick-action__label">Profile</span>
            </a>
        </div>
    </section>

    <section class="member-holo-card member-panel-card mb-4">
        <div class="member-panel-card__header">
            <div>
                <div class="member-section-title mb-1">Recent Ledger Entries</div>
                <div class="member-section-subtitle">Latest member billing activity</div>
            </div>
            <a href="{{ route('member.statement.index') }}" class="member-link-inline">View All</a>
        </div>
        <div class="member-ledger-list">
            @forelse($recentLedgerEntries as $row)
                @php
                    $isCredit = (float) $row->balance_after >= 0;
                    $ledgerRef = strtoupper((string) $row->ref_type) . ($row->ref_id ? ' · REF #' . $row->ref_id : '');
                @endphp
                <div class="member-ledger-row">
                    <div class="member-ledger-row__icon {{ $isCredit ? 'is-credit' : 'is-debit' }}">
                        <i class="bi {{ $isCredit ? 'bi-arrow-down-left' : 'bi-arrow-up-right' }}"></i>
                    </div>
                    <div class="member-ledger-row__body">
                        <div class="member-ledger-row__title">{{ $ledgerRef }}</div>
                        <div class="member-ledger-row__meta">{{ optional($row->entry_date)->format('d M Y') ?: '-' }}</div>
                    </div>
                    <div class="member-ledger-row__amount {{ $isCredit ? 'is-credit' : 'is-debit' }}">
                        PKR {{ number_format((float) $row->balance_after, 2) }}
                    </div>
                </div>
            @empty
                <div class="member-empty-card">No ledger entries found.</div>
            @endforelse
        </div>
    </section>

    <section class="member-holo-card member-panel-card">
        <div class="member-panel-card__header">
            <div>
                <div class="member-section-title mb-1">Recent Complaints</div>
                <div class="member-section-subtitle">Track your submitted requests</div>
            </div>
            <a href="{{ route('member.complaints.index') }}" class="member-link-inline">Open</a>
        </div>
        <div class="member-complaint-list">
            @forelse($recentComplaints as $row)
                <div class="member-complaint-row">
                    <div class="member-complaint-row__body">
                        <div class="member-ledger-row__title">{{ $row->subject }}</div>
                        <div class="member-ledger-row__meta">{{ optional($row->created_at)->format('d M Y') ?: '-' }}</div>
                    </div>
                    <span class="member-status-pill">{{ $row->status }}</span>
                </div>
            @empty
                <div class="member-empty-card">No complaints submitted yet.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
