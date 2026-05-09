@extends('layouts.app')

@section('title', 'My Payments')
@section('page_title', 'My Payments')

@section('content')
<div class="member-module-screen">
    <section class="member-holo-card member-panel-card mb-4">
        <div class="member-panel-card__header">
            <div>
                <div class="member-section-title mb-1">Initiate Payment</div>
                <div class="member-section-subtitle">Create a payment attempt without changing existing backend flow</div>
            </div>
        </div>
        <div class="card-body pt-0">
            <form method="POST" action="{{ route('member.payments.initiate') }}" class="row g-3">
                @csrf
                <div class="col-12">
                    <label class="form-label member-form-label">Bill</label>
                    <select name="bill_id" class="form-select" required>
                        <option value="">Select Bill</option>
                        @foreach($bills as $bill)
                            <option value="{{ $bill->id }}">{{ $bill->month_cycle }} - Bill #{{ $bill->id }} - {{ number_format((float)$bill->net_payable,2) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label member-form-label">Method</label>
                    <select name="payment_method_id" class="form-select" required>
                        <option value="">Method</option>
                        @foreach($methods as $method)
                            <option value="{{ $method->id }}">{{ $method->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label member-form-label">Amount</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="Amount" required>
                </div>
                <div class="col-12">
                    <label class="form-label member-form-label">Manual / Bank Ref</label>
                    <input name="reference_no" class="form-control" placeholder="Manual/Bank Ref">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary member-primary-btn w-100">Initiate</button>
                </div>
            </form>
        </div>
    </section>

    <section class="member-holo-card member-panel-card">
        <div class="member-panel-card__header">
            <div>
                <div class="member-section-title mb-1">Payment History</div>
                <div class="member-section-subtitle">Review previous member payment activity</div>
            </div>
        </div>
        <div class="member-ledger-cards">
            @forelse($payments as $p)
                <article class="member-holo-card member-data-card">
                    <div class="member-data-card__row">
                        <span class="member-data-card__label">Payment ID</span>
                        <span class="member-data-card__value">{{ $p->id }}</span>
                    </div>
                    <div class="member-data-card__row">
                        <span class="member-data-card__label">Bill</span>
                        <span class="member-data-card__value">#{{ $p->bill_id ?? '-' }}</span>
                    </div>
                    <div class="member-data-card__row align-items-start">
                        <span class="member-data-card__label">Reference</span>
                        <span class="member-data-card__value member-data-card__value--wrap">{{ $p->payment_ref ?? $p->reference_no ?? '-' }}</span>
                    </div>
                    <div class="member-data-card__grid">
                        <div>
                            <div class="member-data-card__label">Amount</div>
                            <div class="member-amount">PKR {{ number_format((float)$p->amount,2) }}</div>
                        </div>
                        <div>
                            <div class="member-data-card__label">Method</div>
                            <div class="member-data-card__value">{{ $p->method }}</div>
                        </div>
                    </div>
                    <div class="member-data-card__row">
                        <span class="member-data-card__label">Status</span>
                        <span class="member-status-pill">{{ $p->status }}</span>
                    </div>
                    <div class="member-data-card__row">
                        <span class="member-data-card__label">Date</span>
                        <span class="member-data-card__value">{{ optional($p->created_at)->format('Y-m-d H:i') }}</span>
                    </div>
                </article>
            @empty
                <div class="member-empty-card">No payment history found.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
