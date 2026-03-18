@extends('layouts.app')

@section('title', 'Payments')
@section('page_title', 'Payments')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-header">Create Manual Payment Attempt (No Live Charging)</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.payments.store') }}" class="row g-2">
            @csrf
            <div class="col-md-2">
                <select name="member_id" class="form-select" required>
                    <option value="">Member</option>
                    @foreach($members as $m)
                        <option value="{{ $m->id }}">{{ $m->member_code }} - {{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><input name="bill_id" type="number" min="1" class="form-control" placeholder="Bill ID" required></div>
            <div class="col-md-2">
                <select name="payment_method_id" class="form-select" required>
                    <option value="">Method</option>
                    @foreach($methods as $method)
                        <option value="{{ $method->id }}">{{ $method->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required></div>
            <div class="col-md-2"><input name="reference_no" class="form-control" placeholder="Manual/Bank Ref"></div>
            <div class="col-md-2"><input name="idempotency_key" class="form-control" placeholder="Idempotency Key"></div>
            <div class="col-md-12"><button class="btn btn-primary">Create Attempt</button></div>
        </form>
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
                    <td><span class="badge bg-secondary">{{ $p->status }}</span></td>
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
