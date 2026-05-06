@extends('layouts.app')

@section('title', 'Payments')
@section('page_title', 'Payments')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-header">Create Manual Payment Attempt (No Live Charging)</div>
    <div class="card-body">
        @php
            $manualPaymentTimestamp = now()->format('YmdHis');
            $manualPaymentRandom = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $manualPaymentIdempotencyKey = 'MANPAY-' . $manualPaymentTimestamp . '-' . $manualPaymentRandom;
        @endphp
        <form method="POST" action="{{ route('admin.payments.store') }}" class="row g-2 js-auto-bill-lookup" data-lookup-url="{{ route('admin.payments.member-bill-lookup') }}">
            @csrf
            <div class="col-md-2 position-relative">
                <input name="member_lookup" class="form-control js-member-lookup-input" placeholder="Employee / Member ID" autocomplete="off" required>
                <input type="hidden" name="member_id" class="js-resolved-member-id" required>
                <div id="js-member-suggestions" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1050; display: none;"></div>
            </div>
            <div class="col-md-2">
                <input name="bill_id" type="number" min="1" class="form-control js-bill-id-input" placeholder="Bill ID" required readonly>
                <small id="js-member-bill-status" class="text-muted d-block mt-1">Enter Member Code, numeric Member ID, or name</small>
            </div>
            <div class="col-md-2">
                <select name="payment_method_id" class="form-select" required>
                    <option value="">Method</option>
                    @foreach($methods as $method)
                        <option value="{{ $method->id }}">{{ $method->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}"></div>
            <div class="col-md-2"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required></div>
            <div class="col-md-2"><input name="reference_no" class="form-control" placeholder="Manual/Bank Ref"></div>
            <div class="col-md-2"><input name="idempotency_key" class="form-control" value="{{ $manualPaymentIdempotencyKey }}" readonly></div>
            <div class="col-md-12"><button class="btn btn-primary">Create Attempt</button></div>
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
                    memberInput.value = match.member_code + ' - ' + match.member_name;
                    memberIdInput.value = match.member_id || '';
                    billIdInput.value = match.bill_id || '';
                    clearSuggestions();
                    setStatus('Bill found: #' + match.bill_id, 'text-success');
                };

                const renderSuggestions = (matches) => {
                    suggestionsNode.innerHTML = '';
                    matches.forEach((match) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'list-group-item list-group-item-action';
                        button.textContent = match.member_code + ' - ' + match.member_name;
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

<div class="card shadow-sm mb-3">
    <div class="card-header">Search/Filter</div>
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-2"><input name="member_id" value="{{ request('member_id') }}" class="form-control" placeholder="Member ID"></div>
            <div class="col-md-2"><input name="bill_id" value="{{ request('bill_id') }}" class="form-control" placeholder="Bill ID"></div>
            <div class="col-md-2"><input name="status" value="{{ request('status') }}" class="form-control" placeholder="Status"></div>
            <div class="col-md-2"><input name="method" value="{{ request('method') }}" class="form-control" placeholder="Method"></div>
            <div class="col-md-2"><input name="ref" value="{{ request('ref') }}" class="form-control" placeholder="Ref"></div>
            <div class="col-md-2"><button class="btn btn-outline-primary">Apply</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header">Payments</div>
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>ID</th><th>Member</th><th>Bill</th><th>Ref</th><th>Amount</th><th>Method</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($rows as $p)
                <tr>
                    <td>{{ $p->id }}</td>
                    <td>{{ $p->member->member_code ?? '-' }}</td>
                    <td>{{ $p->bill_id ?? '-' }}</td>
                    <td>{{ $p->payment_ref ?? $p->reference_no ?? '-' }}</td>
                    <td>{{ number_format((float)$p->amount,2) }}</td>
                    <td>{{ $p->method }}</td>
                    <td>
                        @php($displayStatus = $p->status === \App\Models\Payment::STATUS_RECONCILIATION_PENDING ? \App\Models\Payment::STATUS_APPROVED : $p->status)
                        <span class="badge bg-secondary">{{ $displayStatus }}</span>
                    </td>
                    <td>
                        @if(!in_array($p->status, ['SUCCESS','RECONCILED']))
                            <form method="POST" action="{{ route('admin.payments.approve', $p->id) }}">@csrf<button class="btn btn-sm btn-outline-success">Manual Verify Paid</button></form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header">Transactions</div>
    <div class="card-body table-responsive">
        <table class="table table-sm">
            <thead><tr><th>ID</th><th>Payment</th><th>Internal Ref</th><th>Status</th><th>Amount</th><th>Action</th></tr></thead>
            <tbody>
            @foreach($txns as $t)
                <tr>
                    <td>{{ $t->id }}</td><td>{{ $t->payment_id }}</td><td>{{ $t->internal_ref }}</td><td>{{ $t->status }}</td><td>{{ number_format((float)$t->amount,2) }}</td>
                    <td>
                        @if(!in_array($t->status,['SUCCESS','FAILED']))
                            <form method="POST" action="{{ route('admin.payments.transactions.verify', $t->id) }}">@csrf<button class="btn btn-sm btn-outline-success">Verify Success</button></form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Reconciliation</div>
    <div class="card-body table-responsive">
        <table class="table table-sm">
            <thead><tr><th>ID</th><th>Payment</th><th>Status</th><th>Ledger</th><th>Accounting</th><th>Action</th></tr></thead>
            <tbody>
            @foreach($reconciliations as $r)
                <tr>
                    <td>{{ $r->id }}</td><td>{{ $r->payment_id }}</td><td>{{ $r->status }}</td><td>{{ $r->ledger_sync_status }}</td><td>{{ $r->accounting_sync_status }}</td>
                    <td>
                        @if($r->status !== 'RECONCILED')
                            <form method="POST" action="{{ route('admin.payments.reconciliations.reconcile', $r->id) }}">@csrf<button class="btn btn-sm btn-outline-primary">Mark Reconciled</button></form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
