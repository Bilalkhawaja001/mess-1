@extends('layouts.app')

@section('title', 'Mess Bill History')
@section('page_title', 'Mess Bill History')

@section('content')
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Monthly Bills</span>
        <span class="text-muted small">Last 12 cycles</span>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Cycle</th>
                    <th>Period</th>
                    <th>Month</th>
                    <th class="text-end">Total Expenses</th>
                    <th class="text-end">Total Payable</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach($months as $m)
                <tr>
                    <td class="fw-semibold">{{ $m['monthCycle'] }}</td>
                    <td class="small text-muted">
                        {{ $m['rangeStart']->format('d M Y') }} &ndash; {{ $m['rangeEnd']->format('d M Y') }}
                    </td>
                    <td>{{ \Carbon\Carbon::createFromFormat('!Y-m', $m['monthCycle'])->format('F Y') }}</td>
                    <td class="text-end">{{ number_format($m['totalExpenses'], 2) }}</td>
                    <td class="text-end fw-semibold">{{ number_format($m['totalAmount'], 2) }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.admin-mess-bill.index', ['month_cycle' => $m['monthCycle'], 'print' => 1]) }}"
                           target="_blank" class="btn btn-sm btn-outline-secondary">View</a>
                        <a href="{{ route('admin.admin-mess-bill.pdf', $m['monthCycle']) }}"
                           class="btn btn-sm btn-outline-primary">Download PDF</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
