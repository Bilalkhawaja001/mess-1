@extends('layouts.app')

@section('title', 'My Statement')
@section('page_title', 'My Statement')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <div class="text-muted small">Current Outstanding</div>
            <div class="fs-3 fw-bold">{{ number_format($outstandingAmount, 2) }}</div>
            <div class="text-muted small">{{ $member->member_code }} - {{ $member->name }}</div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Ledger Statement</div>
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
                    <th class="text-end">Running Balance</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->date }}</td>
                    <td>{{ $row->description }}</td>
                    <td class="text-end">{{ number_format($row->debit, 2) }}</td>
                    <td class="text-end">{{ number_format($row->credit, 2) }}</td>
                    <td class="text-end">{{ number_format($row->running_balance, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">No statement entries found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
