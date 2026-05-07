@extends('layouts.app')

@section('title', 'Payments')
@section('page_title', 'Payments')

@section('content')
@php
    $paymentRows = $rows ?? collect();
    $transactionRows = $txns ?? collect();
    $reconciliationRows = $reconciliations ?? collect();
    $manualPaymentTimestamp = now()->format('YmdHis');
    $manualPaymentRandom = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    $manualPaymentIdempotencyKey = 'MANPAY-' . $manualPaymentTimestamp . '-' . $manualPaymentRandom;
    $paymentTotalAmount = $paymentRows->sum(function ($row) {
        return (float) ($row->amount ?? 0);
    });
    $successLikeStatuses = ['SUCCESS', 'RECONCILED', \App\Models\Payment::STATUS_APPROVED];
    $successLikeCount = $paymentRows->filter(function ($row) use ($successLikeStatuses) {
        return in_array($row->status, $successLikeStatuses, true)
            || $row->status === \App\Models\Payment::STATUS_RECONCILIATION_PENDING;
    })->count();
    $pendingTxnCount = $transactionRows->filter(function ($txn) {
        return !in_array($txn->status, ['SUCCESS', 'FAILED'], true);
    })->count();
    $openReconciliationCount = $reconciliationRows->where('status', '!=', 'RECONCILED')->count();
@endphp

<div class="payments-page">
<div class="page-hero page-hero-compact mb-4">
    <div>
        <span class="page-hero-kicker">Payments workspace</span>
        <h1 class="page-hero-title">Review payment attempts, transactions, and reconciliation status</h1>
        <p class="page-hero-text mb-0">Manual payment creation, verification, and reconciliation stay functionally unchanged, with cleaner visibility for finance operations.</p>
    </div>
    <div class="page-hero-actions">
        <span class="badge text-bg-light border">{{ $paymentRows->count() }} payment rows</span>
        <span class="badge text-bg-warning">{{ $openReconciliationCount }} open reconciliations</span>
    </div>
</div>

<div class="stats-grid stats-grid-4 mb-4">
    <div class="stat-card stat-card-primary">
        <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
        <div class="stat-label">Payments</div>
        <div class="stat-value">{{ $paymentRows->count() }}</div>
        <div class="stat-help">Visible filtered records</div>
    </div>
    <div class="stat-card stat-card-success">
        <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
        <div class="stat-label">Total Amount</div>
        <div class="stat-value">{{ number_format($paymentTotalAmount, 2) }}</div>
        <div class="stat-help">Visible payment amount sum</div>
    </div>
    <div class="stat-card stat-card-info">
        <div class="stat-icon"><i class="bi bi-patch-check"></i></div>
        <div class="stat-label">Approved / Success</div>
        <div class="stat-value">{{ $successLikeCount }}</div>
        <div class="stat-help">Includes reconciliation pending display state</div>
    </div>
    <div class="stat-card stat-card-warning">
        <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
        <div class="stat-label">Pending Transactions</div>
        <div class="stat-value">{{ $pendingTxnCount }}</div>
        <div class="stat-help">Needs verification attention</div>
    </div>
</div>

<div class="card panel-card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <div class="fw-bold text-dark">Create Manual Payment Attempt (No Live Charging)</div>
            <div class="small text-muted">Use the existing manual intake flow without changing payment behavior.</div>
        </div>
        <span class="badge text-bg-light border">Manual intake</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.payments.store') }}" class="row g-3 js-auto-bill-lookup" data-lookup-url="{{ route('admin.payments.member-bill-lookup') }}">
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
</div>

<div class="card panel-card shadow-sm mb-4">
    <div class="card-body filters-bar">
        <div class="section-heading mb-3">
            <div>
                <h5 class="mb-1">Search and Filter</h5>
                <div class="text-muted">Narrow visible payment records while keeping existing filters intact.</div>
            </div>
        </div>
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-xl-2 col-md-4"><input name="member_id" value="{{ request('member_id') }}" class="form-control" placeholder="Member ID"></div>
            <div class="col-xl-2 col-md-4"><input name="bill_id" value="{{ request('bill_id') }}" class="form-control" placeholder="Bill ID"></div>
            <div class="col-xl-2 col-md-4"><input name="status" value="{{ request('status') }}" class="form-control" placeholder="Status"></div>
            <div class="col-xl-2 col-md-4"><input name="method" value="{{ request('method') }}" class="form-control" placeholder="Method"></div>
            <div class="col-xl-2 col-md-4"><input name="ref" value="{{ request('ref') }}" class="form-control" placeholder="Ref"></div>
            <div class="col-xl-2 col-md-4 d-grid"><button class="btn btn-outline-primary">Apply</button></div>
        </form>
    </div>
</div>

<div class="card panel-card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>Payments</span>
        <span class="badge text-bg-light border">{{ $paymentRows->count() }} rows</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle table-hover mb-0">
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
                    <tr><td colspan="8" class="text-center text-muted py-4">No payment rows found for the current filter.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card panel-card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>Transactions</span>
        <span class="badge text-bg-light border">{{ $transactionRows->count() }} rows</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
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
                    <tr><td colspan="6" class="text-center text-muted py-4">No transactions available.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card panel-card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>Reconciliation</span>
        <span class="badge text-bg-light border">{{ $reconciliationRows->count() }} rows</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
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
                    <tr><td colspan="6" class="text-center text-muted py-4">No reconciliation rows available.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
