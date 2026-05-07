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
    gap: 1.35rem;
}

.payments-redesign .payments-hero {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: minmax(0, 1.6fr) minmax(240px, .7fr);
    gap: 1.25rem;
    padding: 1.5rem 1.6rem;
    border-radius: 24px;
    border: 1px solid #dbe7f5;
    background: linear-gradient(135deg, #ffffff 0%, #f6faff 58%, #eaf3ff 100%);
    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
}

.payments-redesign .payments-hero::after {
    content: '';
    position: absolute;
    right: -48px;
    bottom: -64px;
    width: 180px;
    height: 180px;
    border-radius: 999px;
    background: radial-gradient(circle, rgba(37, 99, 235, 0.14) 0%, rgba(37, 99, 235, 0) 72%);
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
    font-size: clamp(1.8rem, 2.2vw, 2.45rem);
    line-height: 1.12;
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
    align-items: stretch;
    justify-content: flex-end;
}

.payments-redesign .payments-highlight-card {
    width: 100%;
    max-width: 280px;
    align-self: center;
    padding: 1.1rem 1.1rem 1rem;
    border-radius: 20px;
    background: rgba(255, 255, 255, .86);
    border: 1px solid #dbe7f5;
    box-shadow: 0 16px 32px rgba(37, 99, 235, 0.08);
}

.payments-redesign .payments-highlight-label {
    color: #64748b;
    font-size: .76rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    margin-bottom: .55rem;
}

.payments-redesign .payments-highlight-value {
    color: #0f172a;
    font-size: 1.55rem;
    line-height: 1.12;
    font-weight: 800;
}

.payments-redesign .payments-highlight-help {
    margin-top: .4rem;
    color: #64748b;
    font-size: .9rem;
}

.payments-redesign .payments-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
}

.payments-redesign .payments-kpi-card {
    position: relative;
    overflow: hidden;
    min-height: 188px;
    padding: 1.2rem 1.2rem 1.1rem;
    border-radius: 22px;
    border: 1px solid #e1eaf5;
    background: #fff;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.07);
}

.payments-redesign .payments-kpi-card::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 5px;
    background: #2563eb;
}

.payments-redesign .payments-kpi-card.kpi-success::before { background: #10b981; }
.payments-redesign .payments-kpi-card.kpi-info::before { background: #8b5cf6; }
.payments-redesign .payments-kpi-card.kpi-warning::before { background: #f59e0b; }

.payments-redesign .payments-kpi-icon {
    width: 48px;
    height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 15px;
    margin-bottom: 1rem;
    font-size: 1.15rem;
    color: #1d4ed8;
    background: linear-gradient(135deg, #eef4ff, #dbeafe);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, .7);
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
    font-size: .76rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .08em;
    font-weight: 800;
    margin-bottom: .65rem;
}

.payments-redesign .payments-kpi-value {
    font-size: clamp(1.85rem, 2vw, 2.2rem);
    line-height: 1.08;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: .45rem;
    word-break: break-word;
}

.payments-redesign .payments-kpi-help {
    color: #64748b;
    font-size: .92rem;
    line-height: 1.55;
    max-width: 24ch;
}

.payments-redesign .payments-panel {
    border: 1px solid #e2e8f0;
    border-radius: 24px;
    background: #fff;
    box-shadow: 0 16px 38px rgba(15, 23, 42, 0.06);
    overflow: hidden;
}

.payments-redesign .payments-panel-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .75rem;
    padding: 1.1rem 1.3rem;
    border-bottom: 1px solid #edf2f7;
}

.payments-redesign .payments-panel-title {
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
}

.payments-redesign .payments-panel-subtitle {
    color: #64748b;
    font-size: .9rem;
    margin-top: .2rem;
}

.payments-redesign .payments-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 36px;
    padding: .45rem .8rem;
    border-radius: 999px;
    border: 1px solid #dce6f2;
    background: #f8fbff;
    color: #334155;
    font-size: .8rem;
    font-weight: 800;
}

.payments-redesign .payments-panel-body {
    padding: 1.25rem 1.3rem 1.35rem;
}

.payments-redesign .payments-filter-row .form-control,
.payments-redesign .payments-filter-row .form-select,
.payments-redesign .payments-form-grid .form-control,
.payments-redesign .payments-form-grid .form-select {
    min-height: 46px;
    border-radius: 14px;
    border: 1px solid #dbe3ef;
    box-shadow: none;
}

.payments-redesign .payments-filter-row .form-control:focus,
.payments-redesign .payments-filter-row .form-select:focus,
.payments-redesign .payments-form-grid .form-control:focus,
.payments-redesign .payments-form-grid .form-select:focus {
    border-color: rgba(37, 99, 235, .45);
    box-shadow: 0 0 0 .2rem rgba(37, 99, 235, .1);
}

.payments-redesign .payments-panel .btn {
    border-radius: 14px;
    font-weight: 700;
}

.payments-redesign .payments-table-wrap {
    overflow: hidden;
}

.payments-redesign .payments-table-wrap .table-responsive {
    border-top: 1px solid #edf2f7;
}

.payments-redesign .payments-table-wrap .table {
    margin-bottom: 0;
}

.payments-redesign .payments-table-wrap .table thead th {
    background: #f8fbff;
    color: #64748b;
    text-transform: uppercase;
    font-size: .73rem;
    letter-spacing: .08em;
    font-weight: 800;
    border-bottom: 1px solid #e5edf7;
    padding: .95rem 1rem;
}

.payments-redesign .payments-table-wrap .table tbody td {
    padding: .95rem 1rem;
    vertical-align: middle;
    border-color: #edf2f7;
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
        padding: 1.2rem;
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
        <article class="payments-kpi-card">
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
            <form method="POST" action="{{ route('admin.payments.store') }}" class="row g-3 js-auto-bill-lookup payments-form-grid" data-lookup-url="{{ route('admin.payments.member-bill-lookup') }}">
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
                <div class="col-xl-2 col-md-4">
                    <label class="form-label">Reference</label>
                    <input name="reference_no" class="form-control" placeholder="Manual/Bank Ref">
                </div>
                <div class="col-xl-4 col-md-6">
                    <label class="form-label">Idempotency Key</label>
                    <input name="idempotency_key" class="form-control" value="{{ $manualPaymentIdempotencyKey }}" readonly>
                </div>
                <div class="col-xl-2 col-md-3 d-grid">
                    <button class="btn btn-primary">Create Attempt</button>
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
                <div class="col-xl-2 col-md-4"><label class="form-label">Ref</label><input name="ref" value="{{ request('ref') }}" class="form-control" placeholder="Ref"></div>
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
                <thead><tr><th>ID</th><th>Member</th><th>Bill</th><th>Ref</th><th>Amount</th><th>Method</th><th>Status</th><th>Actions</th></tr></thead>
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
                            @php($displayStatus = $p->status === \App\Models\Payment::STATUS_RECONCILIATION_PENDING ? \App\Models\Payment::STATUS_APPROVED : $p->status)
                            <span class="badge {{ in_array($displayStatus, ['APPROVED','SUCCESS','RECONCILED'], true) ? 'bg-success' : 'bg-secondary' }}">{{ $displayStatus }}</span>
                        </td>
                        <td>
                            @if(!in_array($p->status, ['SUCCESS','RECONCILED']))
                                <form method="POST" action="{{ route('admin.payments.approve', $p->id) }}">@csrf<button class="btn btn-sm btn-outline-success">Manual Verify Paid</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="payments-empty">No payment rows found for the current filter.</td></tr>
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
@endsection
