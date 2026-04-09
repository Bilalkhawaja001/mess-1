@extends('layouts.app')

@section('title', 'Rates')
@section('page_title', 'Rate Policies')

@push('styles')
<style>
    .rates-toolbar {
        display: grid;
        grid-template-columns: 1.35fr 0.85fr 0.9fr 0.9fr auto;
        gap: 14px;
        align-items: end;
    }

    .rates-toolbar label {
        display: block;
        font-size: 0.9rem;
        font-weight: 700;
        margin-bottom: 8px;
        color: #334155;
    }

    .rates-toolbar .form-control,
    .rates-toolbar .form-select {
        min-height: 40px;
        border-radius: 14px;
    }

    .rates-toolbar .btn {
        min-height: 40px;
        padding: 0.6rem 1.1rem;
        white-space: nowrap;
    }

    .rates-table-wrap {
        overflow-x: auto;
    }

    .rates-table {
        min-width: 980px;
        font-size: 0.95rem;
    }

    .rates-table thead th {
        white-space: nowrap;
        padding: 0.8rem 0.85rem;
        font-size: 0.78rem;
    }

    .rates-table tbody td {
        white-space: nowrap;
        padding: 0.72rem 0.85rem;
        vertical-align: middle;
    }

    .rates-action-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }

    .rates-action-group form {
        margin: 0;
    }

    .rates-action-group .btn {
        padding: 0.42rem 0.78rem;
        border-radius: 12px;
        font-size: 0.86rem;
        line-height: 1.1;
        min-width: auto;
        white-space: nowrap;
    }

    .badge-rate-yes {
        color: #166534;
        font-weight: 700;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 72px;
        padding: 0.38rem 0.72rem;
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 800;
    }

    .status-pill-active {
        background: rgba(22, 163, 74, 0.14);
        color: #15803d;
    }

    .status-pill-locked {
        background: rgba(148, 163, 184, 0.18);
        color: #475569;
    }

    @media (max-width: 991.98px) {
        .rates-toolbar {
            grid-template-columns: 1fr 1fr;
        }

        .rates-toolbar .rates-submit {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 575.98px) {
        .rates-toolbar {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-header">Add Rate Policy</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.rates.store') }}" class="rates-toolbar">
            @csrf
            <div>
                <label for="rate_type">Type</label>
                <select id="rate_type" name="rate_type" class="form-select" required>
                    @php
                        $types = $rows->pluck('rate_type')->unique()->values();
                    @endphp
                    @forelse($types as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @empty
                        <option value="GUEST">GUEST</option>
                        <option value="RATE_PER_DAY_CENTRALIZED">RATE_PER_DAY_CENTRALIZED</option>
                        <option value="RATE_PER_DAY_CONTRACTORS">RATE_PER_DAY_CONTRACTORS</option>
                        <option value="RATE_PER_DAY_EXECUTIVE">RATE_PER_DAY_EXECUTIVE</option>
                    @endforelse
                </select>
            </div>
            <div>
                <label for="value">Value</label>
                <input id="value" type="number" step="0.0001" name="value" class="form-control" required>
            </div>
            <div>
                <label for="effective_from">From</label>
                <input id="effective_from" type="date" name="effective_from" class="form-control" required>
            </div>
            <div>
                <label for="effective_to">To</label>
                <input id="effective_to" type="date" name="effective_to" class="form-control">
            </div>
            <div class="rates-submit">
                <button class="btn btn-primary w-100">Add Pending</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header">Bulk Import Rates</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.rates.import') }}" enctype="multipart/form-data" class="row g-2 align-items-center">
            @csrf
            <div class="col-md-6"><input type="file" name="file" class="form-control" accept=".csv,.txt" required></div>
            <div class="col-md-3"><button class="btn btn-outline-primary">Import Rates CSV</button></div>
            <div class="col-12 text-muted small">Headers: rate_type,value,effective_from,effective_to,is_active</div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Rate List</div>
    <div class="card-body">
        <div class="table-wrap rates-table-wrap">
            <table class="table table-sm align-middle rates-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Approved</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $r)
                        <tr>
                            <td>{{ $r->id }}</td>
                            <td>{{ $r->rate_type }}</td>
                            <td>{{ rtrim(rtrim(number_format((float) $r->value, 2, '.', ''), '0'), '.') }}</td>
                            <td>{{ optional($r->effective_from)->format('Y-m-d') }}</td>
                            <td>{{ optional($r->effective_to)->format('Y-m-d') ?: 'Open' }}</td>
                            <td><span class="badge-rate-yes">{{ $r->approved_at ? 'YES' : 'NO' }}</span></td>
                            <td>
                                <span class="status-pill {{ $r->is_active ? 'status-pill-active' : 'status-pill-locked' }}">
                                    {{ $r->is_active ? 'Active' : 'Locked' }}
                                </span>
                            </td>
                            <td>
                                <div class="rates-action-group">
                                    <form method="POST" action="{{ route('admin.rates.toggle-approve', $r->id) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success">{{ $r->approved_at ? 'Unapprove' : 'Approve' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.rates.toggle-lock', $r->id) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-warning">{{ $r->is_active ? 'Lock' : 'Unlock' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
