@extends('layouts.app')

@section('title', 'My Payments')
@section('page_title', 'My Payments')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-header">Initiate Payment Attempt (No Live Charging)</div>
    <div class="card-body">
        <form method="POST" action="{{ route('member.payments.initiate') }}" class="row g-2">
            @csrf
            <div class="col-md-3">
                <select name="bill_id" class="form-select" required>
                    <option value="">Select Bill</option>
                    @foreach($bills as $bill)
                        <option value="{{ $bill->id }}">{{ $bill->month_cycle }} - Bill #{{ $bill->id }} - {{ number_format((float)$bill->net_payable,2) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="payment_method_id" class="form-select" required>
                    <option value="">Method</option>
                    @foreach($methods as $method)
                        <option value="{{ $method->id }}">{{ $method->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="Amount" required></div>
            <div class="col-md-2"><input name="reference_no" class="form-control" placeholder="Manual/Bank Ref"></div>
            <div class="col-md-2"><button class="btn btn-primary">Initiate</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Payment History</div>
    <div class="card-body table-responsive">
        <table class="table table-sm member-mobile-table">
            <thead><tr><th>ID</th><th>Bill</th><th>Ref</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            @foreach($payments as $p)
                <tr>
                    <td data-label="ID">{{ $p->id }}</td>
                    <td data-label="Bill">#{{ $p->bill_id ?? '-' }}</td>
                    <td data-label="Ref" class="member-mobile-ref">{{ $p->payment_ref ?? $p->reference_no ?? '-' }}</td>
                    <td data-label="Amount" class="text-end member-mobile-amount">{{ number_format((float)$p->amount,2) }}</td>
                    <td data-label="Method" class="member-mobile-wrap">{{ $p->method }}</td>
                    <td data-label="Status" class="member-mobile-status">{{ $p->status }}</td>
                    <td data-label="Date">{{ optional($p->created_at)->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
