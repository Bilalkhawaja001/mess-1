@extends('layouts.app')

@section('title', 'Payments')
@section('page_title', 'Payments')

@section('content')
@php
    $paymentRows = $rows ?? collect();
    $transactionRows = $txns ?? collect();
    $reconciliationRows = $reconciliations ?? collect();
    $billingMonths = $billingMonths ?? collect();
    $manualPaymentTimestamp = now()->format('YmdHis');
    $manualPaymentRandom = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    $manualPaymentIdempotencyKey = 'MANPAY-' . $manualPaymentTimestamp . '-' . $manualPaymentRandom;
    $openReconciliationCount = $reconciliationRows->where('status', '!=', 'RECONCILED')->count();
@endphp

<script>
    (function () {
        if ('scrollRestoration' in window.history) {
            window.history.scrollRestoration = 'manual';
        }

        const resetPaymentsScroll = function () {
            window.scrollTo(0, 0);
            document.documentElement.scrollTop = 0;
            document.body.scrollTop = 0;

            const pageBody = document.querySelector('.page-body');
            if (pageBody) {
                pageBody.scrollTop = 0;
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', resetPaymentsScroll, { once: true });
        } else {
            resetPaymentsScroll();
        }

        window.addEventListener('load', resetPaymentsScroll, { once: true });
    })();
</script>

<style>
.payments-redesign {
    display: flex;
    flex-direction: column;
    gap: .9rem;
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
}

.payments-redesign .payments-compact-label,
.payments-redesign .form-label {
    margin-bottom: .28rem;
    font-size: .72rem;
    font-weight: 700;
    line-height: 1.2;
    color: #475569;
}

.payments-redesign .payments-hero {
    position: relative;
    overflow: visible;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(180px, 220px);
    align-items: center;
    gap: .8rem;
    padding: .9rem 1rem;
    border-radius: 18px;
    border: 1px solid #dbe7f5;
    background: linear-gradient(135deg, #ffffff 0%, #f6faff 58%, #eaf3ff 100%);
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
}

.payments-redesign .payments-hero::after {
    content: '';
    position: absolute;
    right: -22px;
    bottom: -38px;
    width: 140px;
    height: 140px;
    border-radius: 999px;
    background: radial-gradient(circle, rgba(37, 99, 235, 0.12) 0%, rgba(37, 99, 235, 0) 72%);
    pointer-events: none;
}

.payments-redesign .payments-hero-copy,
.payments-redesign .payments-hero-side {
    position: relative;
    z-index: 1;
}

.payments-redesign .payments-kicker {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .45rem .8rem;
    border-radius: 999px;
    background: #eaf2ff;
    color: #1d4ed8;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    margin-bottom: .95rem;
}

.payments-redesign .payments-hero-title {
    margin: 0;
    font-size: clamp(1.55rem, 2vw, 2rem);
    line-height: 1.08;
    letter-spacing: -.03em;
    font-weight: 800;
    color: #0f172a;
    max-width: 840px;
}

.payments-redesign .payments-hero-text {
    margin: .75rem 0 0;
    max-width: 760px;
    color: #526277;
    font-size: 1rem;
    line-height: 1.7;
}

.payments-redesign .payments-hero-pills {
    display: flex;
    flex-wrap: wrap;
    gap: .65rem;
    margin-top: 1rem;
}

.payments-redesign .payments-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 38px;
    padding: .5rem .85rem;
    border-radius: 999px;
    font-size: .82rem;
    font-weight: 800;
    border: 1px solid transparent;
}

.payments-redesign .payments-pill-primary {
    background: #e8f1ff;
    color: #1d4ed8;
    border-color: #cfe0ff;
}

.payments-redesign .payments-pill-warning {
    background: #fff2c2;
    color: #9a6700;
    border-color: #fde68a;
}

.payments-redesign .payments-hero-side {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    min-width: 0;
}

.payments-redesign .payments-highlight-card {
    width: 100%;
    max-width: 220px;
    align-self: center;
    padding: .72rem .85rem;
    border-radius: 14px;
    background: rgba(255, 255, 255, .94);
    border: 1px solid #dbe7f5;
    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.06);
}

.payments-redesign .payments-highlight-label {
    color: #64748b;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    margin-bottom: .4rem;
}

.payments-redesign .payments-highlight-value {
    color: #0f172a;
    font-size: 1.28rem;
    line-height: 1.08;
    font-weight: 800;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.payments-redesign .payments-highlight-help {
    margin-top: .4rem;
    color: #64748b;
    font-size: .9rem;
}

.payments-redesign .payments-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .75rem;
}

.payments-redesign .payments-kpi-card {
    position: relative;
    overflow: hidden;
    min-height: 146px;
    padding: .88rem .9rem .82rem;
    border-radius: 16px;
    border: 1px solid rgba(214, 226, 240, 0.95);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(246, 250, 255, 0.96) 100%);
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06), 0 1px 0 rgba(255, 255, 255, 0.7) inset;
}

.payments-redesign .payments-kpi-card::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 6px;
    background: linear-gradient(90deg, #2563eb, #60a5fa);
}

.payments-redesign .payments-kpi-card::after {
    content: '';
    position: absolute;
    top: 10px;
    right: -22px;
    width: 110px;
    height: 110px;
    border-radius: 999px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.72) 0%, rgba(255, 255, 255, 0) 72%);
    pointer-events: none;
}

.payments-redesign .payments-kpi-card.kpi-primary {
    background: linear-gradient(180deg, #ffffff 0%, #edf5ff 100%);
    box-shadow: 0 14px 28px rgba(37, 99, 235, 0.12), 0 2px 0 rgba(255, 255, 255, 0.85) inset;
}

.payments-redesign .payments-kpi-card.kpi-success {
    background: linear-gradient(180deg, #ffffff 0%, #ecfdf5 100%);
    box-shadow: 0 14px 28px rgba(16, 185, 129, 0.12), 0 2px 0 rgba(255, 255, 255, 0.85) inset;
}

.payments-redesign .payments-kpi-card.kpi-info {
    background: linear-gradient(180deg, #ffffff 0%, #f4f0ff 100%);
    box-shadow: 0 14px 28px rgba(139, 92, 246, 0.12), 0 2px 0 rgba(255, 255, 255, 0.85) inset;
}

.payments-redesign .payments-kpi-card.kpi-warning {
    background: linear-gradient(180deg, #ffffff 0%, #fff7e8 100%);
    box-shadow: 0 14px 28px rgba(245, 158, 11, 0.14), 0 2px 0 rgba(255, 255, 255, 0.85) inset;
}

.payments-redesign .payments-kpi-card.kpi-success::before { background: linear-gradient(90deg, #10b981, #34d399); }
.payments-redesign .payments-kpi-card.kpi-info::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
.payments-redesign .payments-kpi-card.kpi-warning::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }

.payments-redesign .payments-kpi-icon {
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    margin-bottom: .62rem;
    font-size: .95rem;
    color: #1d4ed8;
    background: linear-gradient(135deg, #eef4ff, #dbeafe);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, .78), 0 5px 12px rgba(37, 99, 235, .1);
}

.payments-redesign .payments-kpi-card.kpi-success .payments-kpi-icon {
    color: #047857;
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
}

.payments-redesign .payments-kpi-card.kpi-info .payments-kpi-icon {
    color: #6d28d9;
    background: linear-gradient(135deg, #f3e8ff, #ede9fe);
}

.payments-redesign .payments-kpi-card.kpi-warning .payments-kpi-icon {
    color: #b45309;
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
}

.payments-redesign .payments-kpi-label {
    font-size: .7rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .08em;
    font-weight: 800;
    margin-bottom: .45rem;
}

.payments-redesign .payments-kpi-value {
    font-size: clamp(1.45rem, 1.7vw, 1.8rem);
    line-height: 1.02;
    font-weight: 900;
    color: #0f172a;
    margin-bottom: .22rem;
    letter-spacing: -.03em;
    text-shadow: 0 1px 0 rgba(255, 255, 255, 0.85);
    word-break: break-word;
}

.payments-redesign .payments-kpi-help {
    color: #64748b;
    font-size: .78rem;
    line-height: 1.35;
    max-width: 24ch;
}

.payments-redesign .payments-panel {
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
    overflow: hidden;
    max-width: 100%;
}

.payments-redesign .payments-panel-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .6rem;
    padding: .75rem .9rem;
    border-bottom: 1px solid #edf2f7;
}

.payments-redesign .payments-panel-title {
    font-size: .95rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.15;
}

.payments-redesign .payments-panel-subtitle {
    color: #64748b;
    font-size: .82rem;
    margin-top: .1rem;
}

.payments-redesign .payments-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 28px;
    padding: .3rem .62rem;
    border-radius: 999px;
    border: 1px solid #dce6f2;
    background: #f8fbff;
    color: #334155;
    font-size: .72rem;
    font-weight: 800;
}

.payments-redesign .payments-panel-body {
    padding: .85rem .9rem .95rem;
}

.payments-redesign .payments-filter-row .form-control,
.payments-redesign .payments-filter-row .form-select,
.payments-redesign .payments-form-grid .form-control,
.payments-redesign .payments-form-grid .form-select {
    min-height: 35px;
    height: 35px;
    padding: .38rem .68rem;
    font-size: 12.5px;
    border-radius: 10px;
    border: 1px solid #dbe3ef;
    box-shadow: none;
}

.payments-redesign .payments-form-grid small {
    font-size: 11px;
    line-height: 1.25;
}

.payments-redesign .payments-form-grid .row,
.payments-redesign .payments-filter-row.row {
    --bs-gutter-x: .65rem;
    --bs-gutter-y: .65rem;
}

.payments-redesign .payments-form-grid .payments-reference-input,
.payments-redesign .payments-filter-row .payments-reference-input {
    font-size: 12.5px;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.payments-redesign .payments-form-grid .payments-reference-input::placeholder,
.payments-redesign .payments-filter-row .payments-reference-input::placeholder {
    font-size: 12.5px;
    white-space: nowrap;
}

.payments-redesign .payments-form-grid .payments-reference-col {
    min-width: 0;
}

.payments-redesign .payments-filter-row .form-control:focus,
.payments-redesign .payments-filter-row .form-select:focus,
.payments-redesign .payments-form-grid .form-control:focus,
.payments-redesign .payments-form-grid .form-select:focus {
    border-color: rgba(37, 99, 235, .45);
    box-shadow: 0 0 0 .2rem rgba(37, 99, 235, .1);
}

.payments-redesign .payments-panel .btn {
    border-radius: 10px;
    font-weight: 700;
    min-height: 34px;
    padding: .36rem .7rem;
    font-size: 12px;
}

.payments-redesign .payments-create-attempt-btn {
    min-height: 35px;
    height: 35px;
    padding: .36rem .78rem;
    border: 1px solid rgba(29, 78, 216, 0.18);
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 55%, #1e40af 100%);
    color: #fff;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .01em;
    box-shadow: 0 6px 14px rgba(37, 99, 235, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.24);
    transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
}

.payments-redesign .payments-create-attempt-btn:hover,
.payments-redesign .payments-create-attempt-btn:focus {
    color: #fff;
    transform: translateY(-1px);
    filter: saturate(1.05);
    box-shadow: 0 16px 28px rgba(37, 99, 235, 0.28), inset 0 1px 0 rgba(255, 255, 255, 0.28);
}

.payments-redesign .payments-table-wrap {
    overflow: hidden;
    max-width: 100%;
}

.payments-redesign .payments-table-wrap .table-responsive {
    border-top: 1px solid #edf2f7;
    overflow-x: auto;
    overflow-y: hidden;
}

.payments-redesign .payments-table-wrap .table {
    margin-bottom: 0;
    width: 100%;
    font-size: 13px;
}

.payments-redesign .payments-table-wrap .table thead th {
    background: #f8fbff;
    color: #64748b;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: .08em;
    font-weight: 800;
    border-bottom: 1px solid #e5edf7;
    padding: 8px 10px;
    white-space: nowrap;
}

.payments-redesign .payments-table-wrap .table tbody td {
    padding: 8px 10px;
    vertical-align: middle;
    border-color: #edf2f7;
    font-size: 13px;
    line-height: 1.25;
}

.payments-redesign .payments-table-wrap .table tbody td .btn {
    min-height: 30px;
    padding: .26rem .58rem;
    font-size: 11.5px;
}

.payments-redesign .payments-table-wrap .table td:nth-child(4),
.payments-redesign .payments-table-wrap .table th:nth-child(4),
.payments-redesign .payments-table-wrap .table td:nth-child(5),
.payments-redesign .payments-table-wrap .table th:nth-child(5),
.payments-redesign .payments-table-wrap .table td:nth-child(7),
.payments-redesign .payments-table-wrap .table th:nth-child(7) {
    white-space: nowrap;
}

.payments-redesign .payments-table-wrap .table tbody tr:hover {
    background: #f8fbff;
}

.payments-redesign .payments-empty {
    padding: 2.5rem 1rem;
    text-align: center;
    color: #64748b;
}

@media (max-width: 1199.98px) {
    .payments-redesign .payments-kpi-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 991.98px) {
    .payments-redesign .payments-hero {
        grid-template-columns: 1fr;
    }

    .payments-redesign .payments-hero-side {
        justify-content: flex-start;
    }

    .payments-redesign .payments-highlight-card {
        max-width: 100%;
    }
}

@media (max-width: 767.98px) {
    .payments-redesign .payments-kpi-grid {
        grid-template-columns: 1fr;
    }

    .payments-redesign .payments-hero {
        padding: 1rem;
        grid-template-columns: 1fr;
    }

    .payments-redesign .payments-hero-side {
        justify-content: flex-start;
    }

    .payments-redesign .payments-highlight-card {
        max-width: 100%;
    }

    .payments-redesign .payments-panel-head {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="payments-redesign">
    <section class="payments-hero">
        <div class="payments-hero-copy">
            <h1 class="payments-hero-title">Payments</h1>
        </div>
        <div class="payments-hero-side">
            <div class="payments-highlight-card">
                <div class="payments-highlight-label">Cycle</div>
                <div class="payments-highlight-value">{{ $selectedMonthCycle ?: 'All' }}</div>
            </div>
        </div>
    </section>

    <section class="payments-kpi-grid">
        <article class="payments-kpi-card kpi-primary">
            <div class="payments-kpi-icon"><i class="bi bi-receipt-cutoff"></i></div>
            <div class="payments-kpi-label">Posted Bills</div>
            <div class="payments-kpi-value">{{ number_format((float) $postedBillAmount, 2) }}</div>
            <div class="payments-kpi-help">{{ $postedBillCount }} bill rows in cycle</div>
        </article>
        <article class="payments-kpi-card kpi-success">
            <div class="payments-kpi-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="payments-kpi-label">Payment Received</div>
            <div class="payments-kpi-value">{{ number_format((float) $receivedPaymentAmount, 2) }}</div>
            <div class="payments-kpi-help">{{ $receivedPaymentCount }} approved / success payments</div>
        </article>
        <article class="payments-kpi-card kpi-info">
            <div class="payments-kpi-icon"><i class="bi bi-hourglass-bottom"></i></div>
            <div class="payments-kpi-label">Pending Balance</div>
            <div class="payments-kpi-value">{{ number_format((float) $pendingBalanceAmount, 2) }}</div>
            <div class="payments-kpi-help">Posted bills minus received payments</div>
        </article>
        <article class="payments-kpi-card kpi-warning">
            <div class="payments-kpi-icon"><i class="bi bi-patch-exclamation"></i></div>
            <div class="payments-kpi-label">Pending Transactions</div>
            <div class="payments-kpi-value">{{ $pendingTransactionCount }}</div>
            <div class="payments-kpi-help">{{ number_format((float) $pendingTransactionAmount, 2) }} awaiting verification</div>
        </article>
    </section>

    <section class="payments-panel">
        <div class="payments-panel-head">
            <div>
                <div class="payments-panel-title">Payments</div>
            </div>
            <span class="payments-chip">Manual intake</span>
        </div>
        <div class="payments-panel-body">
            <form method="POST" action="{{ route('admin.payments.store') }}" class="row g-2 js-auto-bill-lookup payments-form-grid" data-lookup-url="{{ route('admin.payments.member-bill-lookup') }}">
                @csrf
                <div class="col-xl-2 col-md-4 position-relative">
                    <label class="form-label">Member Lookup</label>
                    <input name="member_lookup" class="form-control js-member-lookup-input" placeholder="Employee / Member ID" autocomplete="off" required>
                    <input type="hidden" name="member_id" class="js-resolved-member-id" required>
                    <div id="js-member-suggestions" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1050; display: none;"></div>
                </div>
                <div class="col-xl-2 col-md-4">
                    <label class="form-label">Bill ID</label>
                    <input name="bill_id" type="number" min="1" class="form-control js-bill-id-input" placeholder="Bill ID" required readonly>
                    <small id="js-member-bill-status" class="text-muted d-block mt-1">Enter Member Code, numeric Member ID, or name</small>
                </div>
                <div class="col-xl-2 col-md-4">
                    <label class="form-label">Method</label>
                    <select name="payment_method_id" class="form-select" required>
                        <option value="">Method</option>
                        @foreach($methods as $method)
                            <option value="{{ $method->id }}">{{ $method->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-2 col-md-4">
                    <label class="form-label">Payment Date</label>
                    <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}">
                </div>
                <div class="col-xl-2 col-md-4">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required>
                </div>
                <div class="col-xl-2 col-md-4 payments-reference-col">
                    <label class="form-label payments-compact-label">Reference No.</label>
                    <input name="reference_no" class="form-control payments-reference-input" placeholder="Manual-Bank Ref" autocomplete="off">
                </div>
                <div class="col-xl-3 col-md-6">
                    <label class="form-label payments-compact-label">Idempotency Key</label>
                    <input name="idempotency_key" class="form-control" value="{{ $manualPaymentIdempotencyKey }}" readonly>
                </div>
                <div class="col-xl-2 col-md-3 d-grid align-self-end">
                    <button class="btn payments-create-attempt-btn">Create Attempt</button>
                </div>
            </form>
            <script>
                (function () {
                    const form = document.querySelector('.js-auto-bill-lookup');
                    if (!form) {
                        return;
                    }

                    const lookupUrl = form.dataset.lookupUrl;
                    const memberInput = form.querySelector('.js-member-lookup-input');
                    const memberIdInput = form.querySelector('.js-resolved-member-id');
                    const billIdInput = form.querySelector('.js-bill-id-input');
                    const statusNode = document.getElementById('js-member-bill-status');
                    const suggestionsNode = document.getElementById('js-member-suggestions');
                    let debounceTimer = null;
                    let activeRequest = 0;

                    const setStatus = (message, className = 'text-muted') => {
                        statusNode.textContent = message;
                        statusNode.className = className + ' d-block mt-1';
                    };

                    const clearSuggestions = () => {
                        suggestionsNode.innerHTML = '';
                        suggestionsNode.style.display = 'none';
                    };

                    const resetLookup = (message = 'Enter Member Code, numeric Member ID, or name') => {
                        memberIdInput.value = '';
                        billIdInput.value = '';
                        clearSuggestions();
                        setStatus(message, 'text-muted');
                    };

                    const selectMatch = (match) => {
                        memberInput.value = match.member_code + ' - ' + match.member_name + (match.department ? ' - ' + match.department : '');
                        memberIdInput.value = match.member_id || '';
                        billIdInput.value = match.bill_id || '';
                        clearSuggestions();

                        if (match.bill_id) {
                            setStatus('Bill found: #' + match.bill_id, 'text-success');
                        } else {
                            setStatus('No bill found for this member', 'text-danger');
                        }
                    };

                    const renderSuggestions = (matches) => {
                        suggestionsNode.innerHTML = '';
                        matches.forEach((match) => {
                            const button = document.createElement('button');
                            button.type = 'button';
                            button.className = 'list-group-item list-group-item-action';
                            button.textContent = match.member_code + ' - ' + match.member_name + ' - ' + (match.department || '');
                            button.addEventListener('click', function () {
                                selectMatch(match);
                            });
                            suggestionsNode.appendChild(button);
                        });
                        suggestionsNode.style.display = 'block';
                    };

                    const runLookup = async () => {
                        const value = memberInput.value.trim();
                        if (value === '') {
                            resetLookup();
                            return;
                        }

                        const requestId = ++activeRequest;
                        setStatus('Looking up member...', 'text-secondary');

                        try {
                            const response = await fetch(lookupUrl + '?member_id=' + encodeURIComponent(value), {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                },
                                credentials: 'same-origin'
                            });

                            const data = await response.json();
                            if (requestId !== activeRequest) {
                                return;
                            }

                            if (!response.ok || !data.ok || !Array.isArray(data.matches) || data.matches.length === 0) {
                                memberIdInput.value = '';
                                billIdInput.value = '';
                                clearSuggestions();
                                setStatus(data.message || 'No member found', 'text-danger');
                                return;
                            }

                            if (data.matches.length === 1) {
                                selectMatch(data.matches[0]);
                                return;
                            }

                            memberIdInput.value = '';
                            billIdInput.value = '';
                            renderSuggestions(data.matches);
                            setStatus('Multiple matches found. Select one member.', 'text-warning');
                        } catch (error) {
                            if (requestId !== activeRequest) {
                                return;
                            }
                            memberIdInput.value = '';
                            billIdInput.value = '';
                            clearSuggestions();
                            setStatus('Lookup failed. Try again.', 'text-danger');
                        }
                    };

                    memberInput.addEventListener('input', function () {
                        memberIdInput.value = '';
                        billIdInput.value = '';
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(runLookup, 350);
                    });

                    document.addEventListener('click', function (event) {
                        if (!form.contains(event.target)) {
                            clearSuggestions();
                        }
                    });
                })();
            </script>
        </div>
    </section>

    <section class="payments-panel">
        <div class="payments-panel-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-3">
                <div>
                    <div class="payments-panel-title">Payments</div>
                </div>
                <span class="payments-chip">Filter set</span>
            </div>
            <form method="GET" class="row g-3 align-items-end payments-filter-row">
                <div class="col-xl-2 col-md-4">
                    <label class="form-label">Cycle</label>
                    <select name="month_cycle" class="form-select">
                        <option value="">Latest / Current</option>
                        @foreach($billingMonths as $month)
                            <option value="{{ $month }}" @selected($selectedMonthCycle === $month)>{{ $month }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-2 col-md-4"><label class="form-label">Member</label><input name="member_id" value="{{ request('member_id') }}" class="form-control" placeholder="Member ID"></div>
                <div class="col-xl-2 col-md-4"><label class="form-label">Bill</label><input name="bill_id" value="{{ request('bill_id') }}" class="form-control" placeholder="Bill ID"></div>
                <div class="col-xl-2 col-md-4"><label class="form-label">Status</label><input name="status" value="{{ request('status') }}" class="form-control" placeholder="Status"></div>
                <div class="col-xl-2 col-md-4"><label class="form-label">Method</label><input name="method" value="{{ request('method') }}" class="form-control" placeholder="Method"></div>
                <div class="col-xl-2 col-md-4 payments-reference-col"><label class="form-label payments-compact-label">Ref</label><input name="ref" value="{{ request('ref') }}" class="form-control payments-reference-input" placeholder="Reference"></div>
                <div class="col-xl-2 col-md-4 d-grid"><button class="btn btn-outline-primary">Apply</button></div>
            </form>
        </div>
    </section>

    <section class="payments-panel payments-table-wrap">
        <div class="payments-panel-head">
            <div>
                <div class="payments-panel-title">Payments</div>
            </div>
            <span class="payments-chip">{{ $paymentRows->count() }} rows</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle table-hover">
                <thead><tr><th>ID</th><th>Member</th><th>Bill</th><th>Ref</th><th>Amount</th><th>Method</th><th>Proof</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($paymentRows as $p)
                    <tr>
                        <td>{{ $p->id }}</td>
                        <td>
                            @php($member = $p->member)
                            @if($member)
                                <div class="fw-semibold text-dark">{{ $member->member_code }}</div>
                                <div class="text-muted small">
                                    @if(!empty($member->name)) {{ $member->name }} @endif
                                    @if(!empty($member->department_name)) · {{ $member->department_name }} @endif
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $p->bill_id ?? '-' }}</td>
                        <td>{{ $p->payment_ref ?? $p->reference_no ?? '-' }}</td>
                        <td class="fw-semibold">{{ number_format((float)$p->amount,2) }}</td>
                        <td>{{ $p->method }}</td>
                        <td>
                            @if(!empty($proofMap[$p->id]['url']))
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-dark"
                                    onclick="showPaymentProof('{{ $proofMap[$p->id]['url'] }}', '{{ $p->id }}', '{{ $p->reference_no ?? $p->payment_ref ?? '-' }}')">
                                    View Proof
                                </button>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @php($displayStatus = $p->status === \App\Models\Payment::STATUS_RECONCILIATION_PENDING ? 'PENDING REVIEW' : $p->status)
                            <span class="badge {{ in_array($displayStatus, ['APPROVED','SUCCESS','RECONCILED'], true) ? 'bg-success' : ($displayStatus === 'PENDING REVIEW' ? 'bg-warning text-dark' : 'bg-secondary') }}">{{ $displayStatus }}</span>
                        </td>
                        <td>
                            @if($p->status === \App\Models\Payment::STATUS_RECONCILIATION_PENDING)
                                <div class="d-flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('admin.payments.approve-uploaded-proof', $p->id) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.payments.reject-uploaded-proof', $p->id) }}" onsubmit="return confirm('Reject this payment proof?');">
                                        @csrf
                                        <input type="hidden" name="reject_reason" value="Rejected by admin after proof review">
                                        <button class="btn btn-sm btn-outline-danger">Reject</button>
                                    </form>
                                </div>
                            @elseif(!in_array($p->status, ['SUCCESS','RECONCILED','FAILED']))
                                <form method="POST" action="{{ route('admin.payments.approve', $p->id) }}">@csrf<button class="btn btn-sm btn-outline-success">Manual Verify Paid</button></form>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="payments-empty">No payment rows found for the current filter.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="payments-panel payments-table-wrap">
        <div class="payments-panel-head">
            <div>
                <div class="payments-panel-title">Payments</div>
            </div>
            <span class="payments-chip">{{ $transactionRows->count() }} rows</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead><tr><th>ID</th><th>Payment</th><th>Internal Ref</th><th>Status</th><th>Amount</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($transactionRows as $t)
                    <tr>
                        <td>{{ $t->id }}</td><td>{{ $t->payment_id }}</td><td>{{ $t->internal_ref }}</td><td><span class="badge text-bg-light border">{{ $t->status }}</span></td><td class="fw-semibold">{{ number_format((float)$t->amount,2) }}</td>
                        <td>
                            @if(!in_array($t->status,['SUCCESS','FAILED']))
                                <form method="POST" action="{{ route('admin.payments.transactions.verify', $t->id) }}">@csrf<button class="btn btn-sm btn-outline-success">Verify Success</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="payments-empty">No transactions available.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="payments-panel payments-table-wrap">
        <div class="payments-panel-head">
            <div>
                <div class="payments-panel-title">Payments</div>
            </div>
            <span class="payments-chip">{{ $reconciliationRows->count() }} rows</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead><tr><th>ID</th><th>Payment</th><th>Status</th><th>Ledger</th><th>Accounting</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($reconciliationRows as $r)
                    <tr>
                        <td>{{ $r->id }}</td><td>{{ $r->payment_id }}</td><td><span class="badge {{ $r->status === 'RECONCILED' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $r->status }}</span></td><td>{{ $r->ledger_sync_status }}</td><td>{{ $r->accounting_sync_status }}</td>
                        <td>
                            @if($r->status !== 'RECONCILED')
                                <form method="POST" action="{{ route('admin.payments.reconciliations.reconcile', $r->id) }}">@csrf<button class="btn btn-sm btn-outline-primary">Mark Reconciled</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="payments-empty">No reconciliation rows available.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>


<script>
function showPaymentProof(url, paymentId, ref) {
    let existing = document.getElementById('paymentProofOverlay');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'paymentProofOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,.68);z-index:2147483647;display:flex;align-items:center;justify-content:center;padding:24px;';

    overlay.innerHTML = `
        <div style="background:#fff;border-radius:18px;max-width:900px;width:100%;max-height:90vh;overflow:auto;box-shadow:0 24px 80px rgba(15,23,42,.35);">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #e5e7eb;">
                <div>
                    <div style="font-weight:800;color:#0f172a;font-size:18px;">Payment Proof</div>
                    <div style="color:#64748b;font-size:13px;margin-top:2px;">Payment ID: ${paymentId} | Ref: ${ref}</div>
                </div>
                <button type="button" onclick="document.getElementById('paymentProofOverlay').remove()" style="border:0;background:#f1f5f9;border-radius:999px;width:36px;height:36px;font-size:22px;line-height:1;">×</button>
            </div>
            <div style="padding:20px;text-align:center;">
                <img src="${url}" alt="Payment proof" style="max-width:100%;height:auto;border-radius:14px;border:1px solid #e5e7eb;">
            </div>
        </div>
    `;

    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) overlay.remove();
    });

    document.body.appendChild(overlay);
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const overlay = document.getElementById('paymentProofOverlay');
        if (overlay) overlay.remove();
    }
});
</script>

@endsection
