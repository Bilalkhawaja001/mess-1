@extends('layouts.app')

@section('title', 'My Statement')
@section('page_title', 'My Statement')

@section('content')
<div class="member-module-screen">
    <section class="member-holo-card member-balance-card member-balance-card--compact mb-4">
        <div class="member-balance-card__glow"></div>
        <div class="member-balance-card__content">
            <div class="member-balance-card__label">Current Outstanding</div>
            <div class="member-balance-card__amount">PKR {{ number_format($outstandingAmount, 2) }}</div>
            <div class="member-balance-card__meta mt-3">
                <div>
                    <div class="member-balance-card__label">Member</div>
                    <div class="member-balance-card__date">{{ $member->member_code }} · {{ $member->name }}</div>
                </div>
            </div>
        </div>
    </section>

    <section class="member-holo-card member-panel-card">
        <div class="member-panel-card__header">
            <div>
                <div class="member-section-title mb-1">Ledger Statement</div>
                <div class="member-section-subtitle">Debit, credit and running balance timeline</div>
            </div>
        </div>
        <div class="member-ledger-cards">
            @forelse($rows as $row)
                @php
                    $debit = (float) $row->debit;
                    $credit = (float) $row->credit;
                @endphp
                <article class="member-holo-card member-data-card member-data-card--ledger">
                    <div class="member-data-card__row">
                        <span class="member-data-card__label">Date</span>
                        <span class="member-data-card__value">{{ $row->date }}</span>
                    </div>
                    <div class="member-data-card__row align-items-start">
                        <span class="member-data-card__label">Description</span>
                        <span class="member-data-card__value member-data-card__value--wrap">{{ $row->description }}</span>
                    </div>
                    <div class="member-data-card__grid">
                        <div>
                            <div class="member-data-card__label">Debit</div>
                            <div class="member-amount is-debit">PKR {{ number_format($debit, 2) }}</div>
                        </div>
                        <div>
                            <div class="member-data-card__label">Credit</div>
                            <div class="member-amount is-credit">PKR {{ number_format($credit, 2) }}</div>
                        </div>
                    </div>
                    <div class="member-data-card__row member-data-card__row--balance">
                        <span class="member-data-card__label">Running Balance</span>
                        <span class="member-amount {{ (float) $row->running_balance >= 0 ? 'is-credit' : 'is-debit' }}">PKR {{ number_format($row->running_balance, 2) }}</span>
                    </div>
                </article>
            @empty
                <div class="member-empty-card">No statement entries found.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
