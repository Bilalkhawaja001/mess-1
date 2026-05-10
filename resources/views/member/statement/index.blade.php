@extends('layouts.app')

@section('title', 'My Statement')
@section('page_title', 'My Statement')

@section('content')
<div class="member-statement-shell">
    <section class="member-statement-summary">
        <div class="member-statement-summary__glow"></div>
        <div class="member-statement-summary__content">
            <div class="member-statement-summary__kicker">Current Outstanding</div>
            <div class="member-statement-amount">PKR {{ number_format($outstandingAmount, 2) }}</div>
            <div class="member-statement-summary__meta">
                <span>{{ $member->member_code }}</span>
                <strong>{{ $member->name }}</strong>
            </div>
        </div>
    </section>

    <section class="member-statement-panel">
        <div class="member-statement-panel__head">
            <div>
                <h2 class="member-statement-panel__title">Ledger Statement</h2>
                <p class="member-statement-panel__subtitle">Statement activity with running balance.</p>
            </div>
        </div>

        <div class="member-statement-list">
            @forelse($rows as $row)
                @php
                    $debit = (float) $row->debit;
                    $credit = (float) $row->credit;
                    $balance = (float) $row->running_balance;
                    $entryType = $credit > 0 ? 'credit' : ($debit > 0 ? 'debit' : 'neutral');
                @endphp
                <article class="member-statement-card">
                    <div class="member-statement-card__rail"></div>
                    <div class="member-statement-card__head">
                        <div>
                            <div class="member-statement-card__label">Date</div>
                            <div class="member-statement-card__value">{{ $row->date }}</div>
                        </div>
                        <div class="member-statement-card__badge is-{{ $entryType }}">
                            {{ $credit > 0 ? 'Credit' : ($debit > 0 ? 'Debit' : 'Entry') }}
                        </div>
                    </div>

                    <div class="member-statement-card__block">
                        <div class="member-statement-card__label">Description</div>
                        <div class="member-statement-card__description">{{ $row->description }}</div>
                    </div>

                    <div class="member-statement-card__grid">
                        <div class="member-statement-card__metric">
                            <span class="member-statement-card__label">Debit</span>
                            <strong class="member-statement-amount is-debit">PKR {{ number_format($debit, 2) }}</strong>
                        </div>
                        <div class="member-statement-card__metric">
                            <span class="member-statement-card__label">Credit</span>
                            <strong class="member-statement-amount is-credit">PKR {{ number_format($credit, 2) }}</strong>
                        </div>
                        <div class="member-statement-card__metric member-statement-card__metric--balance">
                            <span class="member-statement-card__label">Running Balance</span>
                            <strong class="member-statement-amount is-balance">PKR {{ number_format($balance, 2) }}</strong>
                        </div>
                    </div>
                </article>
            @empty
                <div class="member-statement-empty">
                    <div class="member-statement-empty__icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div class="member-statement-empty__title">No statement entries found</div>
                    <p class="member-statement-empty__text">Your ledger activity will appear here once transactions are posted.</p>
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
