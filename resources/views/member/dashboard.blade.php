@extends('layouts.app')

@section('title', 'Member Dashboard')
@section('page_title', 'Member Dashboard')

@section('content')
@php
    $displayName = $user->name ?? $user->username;
    $balanceValue = (float) ($outstandingAmount ?? 0);

    if ($balanceValue <= 0) {
        $smartBalanceMessageTop = '🎉 Excellent!';
        $smartBalanceMessageBottom = 'No outstanding balance.';
    } elseif ($balanceValue <= 500) {
        $smartBalanceMessageTop = '🌟 Great!';
        $smartBalanceMessageBottom = 'Balance is under control.';
    } elseif ($balanceValue <= 1000) {
        $smartBalanceMessageTop = '🙂 Gentle reminder';
        $smartBalanceMessageBottom = 'Clear your balance soon.';
    } else {
        $smartBalanceMessageTop = '⏰ Due reminder';
        $smartBalanceMessageBottom = 'Please clear before due date.';
    }

    $dueDate = $lastBill?->due_date;
    $dueDateText = $dueDate ? optional($dueDate)->format('d M Y') : '-';
    $dueBadgeText = '-';

    if ($balanceValue <= 0) {
        $dueDateText = '-';
        $dueBadgeText = 'No dues';
    } elseif ($dueDate) {
        $dueEnd = \Carbon\Carbon::parse($dueDate)->endOfDay();
        $hoursLeft = now()->diffInHours($dueEnd, false);

        if ($hoursLeft < 0) {
            $dueBadgeText = '⚠️ Due date passed';
        } elseif ($hoursLeft <= 72) {
            $dueBadgeText = max(0, (int) ceil($hoursLeft)).' hours left';
        } else {
            $daysLeft = (int) ceil(now()->diffInDays($dueEnd, false));
            $dueBadgeText = $daysLeft <= 1 ? '1 day left' : $daysLeft.' days left';
        }
    }
@endphp

<div class="member-dashboard-screen member-dashboard-shell member-compact-stack member-section-gap">
    @if($memberProfileMissing)
        <div class="alert alert-warning shadow-sm member-alert-card" role="alert">
            Your member profile is not linked yet. Please contact admin.
        </div>
    @endif

    <section class="member-dashboard-card member-dashboard-balance member-card-gap">
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

            <div class="member-dashboard-balance__main-row">
                <div>
                    <div class="member-dashboard-balance__label">Outstanding Balance</div>
                    <div class="member-dashboard-balance__amount">PKR {{ number_format($outstandingAmount, 2) }}</div>
                </div>
                <div class="member-dashboard-smart-message">
                    <div class="member-dashboard-smart-message__top">{{ $smartBalanceMessageTop }}</div>
                    <div class="member-dashboard-smart-message__bottom">{{ $smartBalanceMessageBottom }}</div>
                </div>
            </div>

            <div class="member-dashboard-balance__footer">
                <div class="member-dashboard-balance__meta">
                    <span>Due Date</span>
                    <strong>{{ $dueDateText }}</strong>
                </div>
                <div class="member-dashboard-balance__badge">
                    {{ $dueBadgeText }}
                </div>
            </div>
        </div>
    </section>

    <section class="member-dashboard-panel member-dashboard-panel--compact member-card-gap member-dashboard-today-menu">
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

    <section class="member-dashboard-panel member-dashboard-panel--compact member-card-gap">
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
        </div>
    </section>

    <section class="member-dashboard-panel member-dashboard-panel--compact member-card-gap">
        <div class="member-dashboard-panel__head member-dashboard-panel__head--compact">
            <div>
                <div class="member-dashboard-section-label">Recent Ledger</div>
            </div>
            <a href="{{ route('member.statement.index') }}" class="member-dashboard-link">View all</a>
        </div>

        <div class="member-dashboard-ledger-list">
            @if($lastBill)
                <article class="member-dashboard-ledger-card">
                    <div class="member-dashboard-ledger-card__icon is-debit">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="member-dashboard-ledger-card__body">
                        <div class="member-dashboard-ledger-card__title">Last Bill · REF #{{ $lastBill->id }}</div>
                        <div class="member-dashboard-ledger-card__meta">
                            {{ $lastBill->month_cycle ?? '-' }}
                            · Total Days: {{ (int) data_get($lastBill, 'active_days', 0) }}
                            · Daily Billing Rate: PKR {{ number_format((float) data_get($lastBill, 'rate_per_day', 0), 2) }}
                        </div>
                    </div>
                    <div class="member-dashboard-ledger-card__amount is-debit">
                        PKR {{ number_format((float) data_get($lastBill, 'net_payable', 0), 2) }}
                    </div>
                </article>
            @endif

            @if($lastPayment)
                @php
                    $lastPaymentAmount = (float) (
                        data_get($lastPayment, 'amount')
                        ?? data_get($lastPayment, 'paid_amount')
                        ?? data_get($lastPayment, 'received_amount')
                        ?? data_get($lastPayment, 'net_amount')
                        ?? 0
                    );
                @endphp
                <article class="member-dashboard-ledger-card">
                    <div class="member-dashboard-ledger-card__icon is-credit">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div class="member-dashboard-ledger-card__body">
                        <div class="member-dashboard-ledger-card__title">Last Payment · REF #{{ $lastPayment->id }}</div>
                        <div class="member-dashboard-ledger-card__meta">{{ optional($lastPayment->created_at)->format('d M Y') ?: '-' }}</div>
                    </div>
                    <div class="member-dashboard-ledger-card__amount is-credit">
                        PKR {{ number_format($lastPaymentAmount, 2) }}
                    </div>
                </article>
            @endif

            @if(! $lastBill && ! $lastPayment)
                <div class="member-dashboard-empty">No bill or payment found.</div>
            @endif
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
