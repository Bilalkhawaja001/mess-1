@extends('layouts.app')

@section('title', 'My Payments')
@section('page_title', 'My Payments')

@section('content')
<div class="member-payments-shell">
    <section class="member-payments-form-card">
        <div class="member-payments-form-card__glow"></div>
        <div class="member-payments-form-card__content">
            <div class="member-payments-form-card__head">
                <div>
                    <div class="member-payments-form-card__kicker">My Payments</div>
                    <h2 class="member-payments-form-card__title">Initiate Payment</h2>
                    <p class="member-payments-form-card__subtitle">Start a new payment attempt using your existing member billing flow.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('member.payments.initiate') }}" class="member-payments-form">
                @csrf
                <div class="member-payments-field member-payments-field--wide">
                    <label class="member-payments-label">Bill</label>
                    <select name="bill_id" class="form-select member-payments-select" required>
                        <option value="">Select Bill</option>
                        @foreach($bills as $bill)
                            <option value="{{ $bill->id }}">{{ $bill->month_cycle }} - Bill #{{ $bill->id }} - {{ number_format((float)$bill->net_payable,2) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="member-payments-field">
                    <label class="member-payments-label">Method</label>
                    <select name="payment_method_id" class="form-select member-payments-select" required>
                        <option value="">Method</option>
                        @foreach($methods as $method)
                            <option value="{{ $method->id }}">{{ $method->code }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="member-payments-field">
                    <label class="member-payments-label">Amount</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control member-payments-input" placeholder="Amount" required>
                </div>

                <div class="member-payments-field member-payments-field--wide">
                    <label class="member-payments-label">Manual/Bank Ref</label>
                    <input name="reference_no" class="form-control member-payments-input" placeholder="Manual/Bank Ref">
                </div>

                <div class="member-payments-field member-payments-field--action">
                    <button class="btn member-payments-submit-btn">Initiate</button>
                </div>
            </form>
        </div>
    </section>

    <section class="member-payments-history">
        <div class="member-payments-history__head">
            <div>
                <h2 class="member-payments-history__title">Payment History</h2>
                <p class="member-payments-history__subtitle">Track payment attempts, references, methods, and status updates.</p>
            </div>
        </div>

        <div class="member-payments-list">
            @forelse($payments as $p)
                @php
                    $status = strtoupper((string) $p->status);
                    $statusClass = in_array($status, ['APPROVED', 'SUCCESS'])
                        ? 'success'
                        : (in_array($status, ['FAILED', 'REJECTED'])
                            ? 'danger'
                            : (in_array($status, ['RECONCILIATION_PENDING']) ? 'info' : 'pending'));
                @endphp
                <article class="member-payment-card">
                    <div class="member-payment-card__rail"></div>
                    <div class="member-payment-card__head">
                        <div>
                            <div class="member-payment-card__label">Payment ID</div>
                            <div class="member-payment-card__value">#{{ $p->id }}</div>
                        </div>
                        <div class="member-payment-status is-{{ $statusClass }}">{{ $p->status }}</div>
                    </div>

                    <div class="member-payment-card__grid">
                        <div class="member-payment-card__item">
                            <span class="member-payment-card__label">Bill</span>
                            <strong class="member-payment-card__text">#{{ $p->bill_id ?? '-' }}</strong>
                        </div>
                        <div class="member-payment-card__item">
                            <span class="member-payment-card__label">Method</span>
                            <strong class="member-payment-card__text">{{ $p->method }}</strong>
                        </div>
                        <div class="member-payment-card__item member-payment-card__item--amount">
                            <span class="member-payment-card__label">Amount</span>
                            <strong class="member-payment-amount">PKR {{ number_format((float)$p->amount,2) }}</strong>
                        </div>
                    </div>

                    <div class="member-payment-card__stack">
                        <div class="member-payment-card__item member-payment-card__item--full">
                            <span class="member-payment-card__label">Reference</span>
                            <strong class="member-payment-card__text member-payment-card__text--break">{{ $p->payment_ref ?? $p->reference_no ?? '-' }}</strong>
                        </div>
                        <div class="member-payment-card__item member-payment-card__item--full">
                            <span class="member-payment-card__label">Date</span>
                            <strong class="member-payment-card__text">{{ optional($p->created_at)->format('Y-m-d H:i') }}</strong>
                        </div>
                    </div>
                </article>
            @empty
                <div class="member-payments-empty">
                    <div class="member-payments-empty__icon"><i class="fas fa-wallet"></i></div>
                    <div class="member-payments-empty__title">No payments found</div>
                    <p class="member-payments-empty__text">Your payment attempts and status updates will appear here.</p>
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
