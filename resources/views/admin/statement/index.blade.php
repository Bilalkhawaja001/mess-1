@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.statement.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Member</label>
                    <select name="member_id" class="form-select">
                        @foreach($members as $m)
                            <option value="{{ $m->id }}" @selected((int) $memberId === (int) $m->id)>
                                {{ $m->member_code }} - {{ $m->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Single Month</label>
                    <input type="month" name="single_month" value="{{ $singleMonth }}" class="form-control">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">From Month</label>
                    <input type="month" name="from_month" value="{{ $fromMonth }}" class="form-control">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">To Month</label>
                    <input type="month" name="to_month" value="{{ $toMonth }}" class="form-control">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-secondary flex-fill" type="submit">View</button>
                    <button class="btn btn-success flex-fill" type="submit" name="export" value="csv">Excel</button>
                </div>

                <div class="col-md-2">
                    <button type="button" onclick="window.print()" class="btn btn-primary w-100">Print Statement</button>
                </div>
            </form>
        </div>
    </div>

    <div class="statement-print mx-auto bg-white border rounded shadow-sm p-3" style="max-width: 920px;">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h3 class="mb-1 fw-bold">Executive Mess</h3>
                <div class="text-muted">Member Account Statement</div>
            </div>
            <div class="text-muted small">Generated: {{ now()->format('Y-m-d') }}</div>
        </div>

        <hr>

        <div class="row small fw-semibold mb-2">
            <div class="col-md-2">Member ID:</div>
            <div class="col-md-4 fw-normal">{{ $member->member_code ?? '-' }}</div>
            <div class="col-md-2">Name:</div>
            <div class="col-md-4 fw-normal">{{ $member->name ?? '-' }}</div>
        </div>

        <div class="row small fw-semibold mb-2">
            <div class="col-md-2">Department:</div>
            <div class="col-md-4 fw-normal">{{ $member->department_name ?? '-' }}</div>
            <div class="col-md-2">Mess:</div>
            <div class="col-md-4 fw-normal">{{ $messName }}</div>
        </div>

        <div class="row small fw-semibold mb-2">
            <div class="col-md-2">Join Date:</div>
            <div class="col-md-4 fw-normal">{{ $member->join_date ?? '-' }}</div>
            <div class="col-md-2">Leave Date:</div>
            <div class="col-md-4 fw-normal">{{ $member->leave_date ?? '-' }}</div>
        </div>

        <div class="row small fw-semibold mb-3">
            <div class="col-md-2">Statement Month:</div>
            <div class="col-md-10 fw-normal">{{ $fromMonth }} to {{ $toMonth }}</div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <div class="border rounded p-2">
                    <div class="text-muted">Opening Balance</div>
                    <div class="h5 mb-0 fw-bold">{{ number_format($openingBalance, 2) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-2">
                    <div class="text-muted">Total Debit</div>
                    <div class="h5 mb-0 fw-bold">{{ number_format($totalDebit, 2) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-2">
                    <div class="text-muted">Total Credit</div>
                    <div class="h5 mb-0 fw-bold">{{ number_format($totalCredit, 2) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-2">
                    <div class="text-muted">Closing Balance</div>
                    <div class="h5 mb-0 fw-bold">{{ number_format($closingBalance, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Month</th>
                        <th>Days</th>
                        <th>Rate Per Day</th>
                        <th>Total Amount</th>
                        <th>Ref Type</th>
                        <th>Ref ID</th>
                        <th>Debit</th>
                        <th>Credit</th>
                        <th>Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->month }}</td>
                            <td>{{ $row->days }}</td>
                            <td>{{ $row->rate_per_day !== '' ? number_format((float) $row->rate_per_day, 2) : '' }}</td>
                            <td>{{ number_format((float) $row->total_amount, 2) }}</td>
                            <td>{{ $row->ref_type }}</td>
                            <td>{{ $row->ref_id }}</td>
                            <td>{{ number_format((float) $row->debit, 2) }}</td>
                            <td>{{ number_format((float) $row->credit, 2) }}</td>
                            <td>{{ number_format((float) $row->running_balance, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No statement rows found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="text-center text-muted small mt-3">
            This is a system-generated statement and does not require any signature or stamp.
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }

    .statement-print,
    .statement-print * {
        visibility: visible;
    }

    .statement-print {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        max-width: 100% !important;
        box-shadow: none !important;
        border: none !important;
    }

    .sidebar,
    nav,
    header,
    .btn,
    form {
        display: none !important;
    }
}
</style>
@endsection
