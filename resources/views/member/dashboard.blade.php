@extends('layouts.app')

@section('title', 'Member Dashboard')
@section('page_title', 'Member Dashboard')

@section('content')
@php
    $displayName = $user->name ?? $user->username;
    $dueDate = now()->addDays(5);
    $dueDays = max(1, now()->diffInDays($dueDate, false));
@endphp

<div class="member-dashboard-screen member-dashboard-shell">
    @if($memberProfileMissing)
        <div class="alert alert-warning shadow-sm member-alert-card" role="alert">
            Your member profile is not linked yet. Please contact admin.
        </div>
    @endif

    <section class="member-dashboard-card member-dashboard-balance mb-3">
        <div class="member-dashboard-balance__mesh"></div>
        <div class="member-dashboard-balance__glow"></div>
        <div class="member-dashboard-balance__content">
            <div class="member-dashboard-balance__top">
                <div>
                    <div class="member-dashboard-kicker">Member Dashboard</div>
                    <h2 class="member-dashboard-title">{{ $displayName }}</h2>
                </div>
                <div class="member-dashboard-balance__orb"><i class="bi bi-wallet2"></i></div>
            </div>

            <div class="member-dashboard-balance__label">Outstanding Balance</div>
            <div class="member-dashboard-balance__amount">PKR {{ number_format($outstandingAmount, 2) }}</div>

            <div class="member-dashboard-balance__footer">
                <div class="member-dashboard-balance__meta">
                    <span>Due Date</span>
                    <strong>{{ $dueDate->format('d M Y') }}</strong>
                </div>
                <div class="member-dashboard-balance__badge">{{ $dueDays }} days left</div>
            </div>
        </div>
    </section>

    <section class="member-dashboard-panel member-dashboard-panel--compact mb-3">
        <div class="member-dashboard-panel__head member-dashboard-panel__head--compact">
            <div>
                <div class="member-dashboard-section-label">Quick Actions</div>
            </div>
        </div>
        <div class="member-dashboard-actions">
            <a class="member-dashboard-action" href="{{ route('member.statement.index') }}">
                <span class="member-dashboard-action__icon"><i class="bi bi-journal-text"></i></span>
                <span class="member-dashboard-action__label">Statement</span>
            </a>
            <a class="member-dashboard-action" href="{{ route('member.payments.index') }}">
                <span class="member-dashboard-action__icon"><i class="bi bi-credit-card-2-front"></i></span>
                <span class="member-dashboard-action__label">Payment</span>
            </a>
            <a class="member-dashboard-action" href="{{ route('member.complaints.index') }}">
                <span class="member-dashboard-action__icon"><i class="bi bi-headset"></i></span>
                <span class="member-dashboard-action__label">Complaint</span>
            </a>
            <a class="member-dashboard-action" href="{{ route('member.profile.index') }}">
                <span class="member-dashboard-action__icon"><i class="bi bi-person"></i></span>
                <span class="member-dashboard-action__label">Profile</span>
            </a>
            <a class="member-dashboard-action member-dashboard-action--menu" href="{{ route('member.menu.index') }}">
                <span class="member-dashboard-action__icon"><i class="bi bi-cup-hot"></i></span>
                <span class="member-dashboard-action__label">Menu</span>
            </a>
        </div>
    </section>

    <section class="member-dashboard-panel member-dashboard-panel--compact mb-3 member-dashboard-today-menu">
        <div class="member-dashboard-panel__head member-dashboard-panel__head--compact">
            <div>
                <div class="member-dashboard-section-label">Today's Menu</div>
            </div>
            <a href="{{ route('member.menu.index') }}" class="member-dashboard-link">Full menu</a>
        </div>

        @php
            $todayMenuAvailable = collect($todayMenu ?? [])->contains(fn ($value) => $value !== '-');
        @endphp

        @if($todayMenuAvailable)
            <div class="member-dashboard-today-menu__grid">
                <article class="member-dashboard-menu-item">
                    <div class="member-dashboard-menu-item__label">Breakfast</div>
                    <div class="member-dashboard-menu-item__text" style="white-space: pre-line">{{ $todayMenu['BREAKFAST'] ?? '-' }}</div>
                </article>
                <article class="member-dashboard-menu-item">
                    <div class="member-dashboard-menu-item__label">Lunch</div>
                    <div class="member-dashboard-menu-item__text" style="white-space: pre-line">{{ $todayMenu['LUNCH'] ?? '-' }}</div>
                </article>
                <article class="member-dashboard-menu-item">
                    <div class="member-dashboard-menu-item__label">Dinner</div>
                    <div class="member-dashboard-menu-item__text" style="white-space: pre-line">{{ $todayMenu['DINNER'] ?? '-' }}</div>
                </article>
                <article class="member-dashboard-menu-item">
                    <div class="member-dashboard-menu-item__label">Tea / Other</div>
                    <div class="member-dashboard-menu-item__text" style="white-space: pre-line">{{ $todayMenu['TEA_OTHER'] ?? '-' }}</div>
                </article>
            </div>
        @else
            <div class="member-dashboard-empty">Today's menu is not available yet.</div>
        @endif
    </section>

    <section class="member-dashboard-panel member-dashboard-panel--compact mb-3">
        <div class="member-dashboard-panel__head member-dashboard-panel__head--compact">
            <div>
                <div class="member-dashboard-section-label">Recent Ledger</div>
            </div>
            <a href="{{ route('member.statement.index') }}" class="member-dashboard-link">View all</a>
        </div>

        <div class="member-dashboard-ledger-list">
            @forelse($recentLedgerEntries as $row)
                @php
                    $isCredit = (float) $row->balance_after >= 0;
                    $ledgerRef = strtoupper((string) $row->ref_type) . ($row->ref_id ? ' · REF #' . $row->ref_id : '');
                @endphp
                <article class="member-dashboard-ledger-card">
                    <div class="member-dashboard-ledger-card__icon {{ $isCredit ? 'is-credit' : 'is-debit' }}">
                        <i class="bi {{ $isCredit ? 'bi-arrow-down-left' : 'bi-arrow-up-right' }}"></i>
                    </div>
                    <div class="member-dashboard-ledger-card__body">
                        <div class="member-dashboard-ledger-card__title">{{ $ledgerRef }}</div>
                        <div class="member-dashboard-ledger-card__meta">{{ optional($row->entry_date)->format('d M Y') ?: '-' }}</div>
                    </div>
                    <div class="member-dashboard-ledger-card__amount {{ $isCredit ? 'is-credit' : 'is-debit' }}">
                        PKR {{ number_format((float) $row->balance_after, 2) }}
                    </div>
                </article>
            @empty
                <div class="member-dashboard-empty">No ledger entries found.</div>
            @endforelse
        </div>
    </section>

    <section class="member-dashboard-panel member-dashboard-panel--compact">
        <div class="member-dashboard-panel__head member-dashboard-panel__head--compact">
            <div>
                <div class="member-dashboard-section-label">Recent Complaints</div>
            </div>
            <a href="{{ route('member.complaints.index') }}" class="member-dashboard-link">Open</a>
        </div>

        <div class="member-dashboard-complaints">
            @forelse($recentComplaints as $row)
                <article class="member-dashboard-complaint-card">
                    <div class="member-dashboard-complaint-card__body">
                        <div class="member-dashboard-ledger-card__title">{{ $row->subject }}</div>
                        <div class="member-dashboard-ledger-card__meta">{{ optional($row->created_at)->format('d M Y') ?: '-' }}</div>
                    </div>
                    <span class="member-dashboard-status">{{ $row->status }}</span>
                </article>
            @empty
                <div class="member-dashboard-empty">No complaints submitted yet.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
