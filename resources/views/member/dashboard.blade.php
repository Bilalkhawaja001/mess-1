@extends('layouts.app')

@section('title', 'Member Dashboard')
@section('page_title', 'Member Dashboard')

@section('content')
<div class="hero-panel p-4 mb-4">
    <div class="section-kicker mb-3"><i class="bi bi-person-workspace"></i> Member Workspace</div>
    <h4 class="mb-2 fw-bold">Welcome, {{ $user->name ?? $user->username }}</h4>
    <p class="text-muted mb-0">Your account view is limited to your own mess profile only.</p>
</div>

@if($memberProfileMissing)
<div class="alert alert-warning shadow-sm" role="alert">
    Your member profile is not linked yet. Please contact admin.
</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-6 col-xl-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">Total Outstanding</div><div class="fs-4 fw-bold">{{ number_format($outstandingAmount, 2) }}</div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">Current Month Bill</div><div class="fs-4 fw-bold">{{ number_format($currentMonthBill, 2) }}</div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">Last Payment</div><div class="fs-6 fw-bold">{{ $lastPayment ? number_format((float) $lastPayment->amount, 2) : '-' }}</div><div class="text-muted small">{{ $lastPayment ? optional($lastPayment->created_at)->format('Y-m-d H:i') : 'No payment yet' }}</div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">Open Complaints</div><div class="fs-4 fw-bold">{{ $openComplaintsCount }}</div></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6 col-xl-2"><a class="btn btn-primary w-100" href="{{ route('member.payments.index') }}">Pay</a></div>
    <div class="col-md-6 col-xl-2"><a class="btn btn-outline-primary w-100" href="{{ route('member.statement.index') }}">My Statement</a></div>
    <div class="col-md-6 col-xl-2"><a class="btn btn-outline-primary w-100" href="{{ route('member.menu.index') }}">Menu</a></div>
    <div class="col-md-6 col-xl-3"><a class="btn btn-outline-primary w-100" href="{{ route('member.complaints.index') }}">Complaint / Suggestion</a></div>
    <div class="col-md-6 col-xl-3"><a class="btn btn-outline-primary w-100" href="{{ route('member.profile.index') }}">My Profile</a></div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">Recent Ledger Entries</div>
            <div class="card-body table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Date</th><th>Ref</th><th class="text-end">Balance</th></tr></thead>
                    <tbody>
                    @forelse($recentLedgerEntries as $row)
                        <tr>
                            <td>{{ optional($row->entry_date)->format('Y-m-d') }}</td>
                            <td>{{ strtoupper((string) $row->ref_type) }} @if($row->ref_id)#{{ $row->ref_id }}@endif</td>
                            <td class="text-end">{{ number_format((float) $row->balance_after, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">No ledger entries found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">Recent Complaints / Suggestions</div>
            <div class="card-body table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Date</th><th>Subject</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($recentComplaints as $row)
                        <tr>
                            <td>{{ optional($row->created_at)->format('Y-m-d') }}</td>
                            <td>{{ $row->subject }}</td>
                            <td><span class="badge bg-secondary">{{ $row->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">No complaints submitted yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
