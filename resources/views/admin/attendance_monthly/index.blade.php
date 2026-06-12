@extends('layouts.app')
@section('title','Monthly Attendance')
@section('page_title','Monthly Attendance')
@section('content')
<div class="row g-2 mb-3">
    @foreach($monthCards as $card)
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="fw-semibold">{{ $card['month_cycle'] }}</div>
                    <div class="small">Contractors: {{ $card['contractors'] }}</div>
                    <div class="small">Executive: {{ $card['executive'] }}</div>
                    <div class="small">Centralized: {{ $card['centralized'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card shadow-sm mb-3"><div class="card-body">
<form method="GET" class="row g-2 align-items-end">
<div class="col-md-3"><label class="form-label">Month Cycle</label><input name="month_cycle" type="month" value="{{ $monthCycle }}" class="form-control" placeholder="YYYY-MM"></div>
<div class="col-md-2"><button class="btn btn-outline-primary">Load</button></div>
</form></div></div>

<div class="card shadow-sm mb-3">
    <div class="card-header">CSV Template / Bulk Upload</div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-3 align-items-end">
            <div>
                <a href="{{ route('admin.attendance-monthly.template') }}" class="btn btn-outline-secondary">Download CSV Template</a>
            </div>
            <form method="POST" action="{{ route('admin.attendance-monthly.import') }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-end">
                @csrf
                <div>
                    <label class="form-label mb-1">CSV File</label>
                    <input type="file" name="csv_file" accept=".csv,text/csv" class="form-control">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">Upload CSV</button>
                </div>
            </form>
        </div>
        <div class="form-text mt-2">Required columns: month_cycle, member_code, present_days</div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header">Manual Monthly Attendance Entry</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.attendance-monthly.manual') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Month</label>
                <input type="month" name="month_cycle" value="{{ $monthCycle }}" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Search Member</label>
                <select name="member_id" class="form-select" required>
                    <option value="">Select member</option>
                    @foreach($rows as $r)
                        <option value="{{ $r['member']->id }}">{{ $r['member']->member_code }} — {{ $r['member']->name }} — {{ $r['member']->department_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Present Days</label>
                <input type="number" name="present_days" min="0" max="31" class="form-control" required>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span>Monthly Present Days</span>
        <span class="small text-muted" id="attendanceMemberResultCount">{{ count($rows) }} members</span>
    </div>
    <div class="card-body">
        <div class="row g-2 align-items-end mb-3">
            <div class="col-md-6 col-lg-5">
                <label class="form-label">Find Member</label>
                <input
                    type="search"
                    id="attendanceMemberSearch"
                    class="form-control"
                    placeholder="Search by code, name, department..."
                    autocomplete="off"
                >
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-secondary w-100" id="attendanceMemberSearchClear">Clear</button>
            </div>
        </div>

        <div class="table-responsive">
            <form method="POST" action="{{ route('admin.attendance-monthly.store') }}">@csrf
                <input type="hidden" name="month_cycle" value="{{ $monthCycle }}">
                <table class="table table-sm align-middle" id="attendanceMonthlyTable">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Present Days</th>
                            <th>Locked</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($rows as $i => $r)
                        <tr data-member-search="{{ strtolower(trim(($r['member']->member_code ?? '') . ' ' . ($r['member']->name ?? '') . ' ' . ($r['member']->department_name ?? '') . ' ' . ($r['member']->mess_code ?? '') . ' ' . ($r['member']->member_type ?? ''))) }}">
                            <td>
                                {{ $r['member']->member_code }} - {{ $r['member']->name }}
                                @if(!empty($r['member']->department_name))
                                    <div class="small text-muted">{{ $r['member']->department_name }}</div>
                                @endif
                                <input type="hidden" name="rows[{{ $i }}][member_id]" value="{{ $r['member']->id }}">
                            </td>
                            <td>
                                <input type="number" min="0" max="31" class="form-control form-control-sm" name="rows[{{ $i }}][present_days]" value="{{ $r['present_days'] }}">
                            </td>
                            <td>{{ $r['is_locked'] ? 'Yes' : 'No' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary">Save</button>
                    <button name="approve" value="1" class="btn btn-success">Save & Approve/Lock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('attendanceMemberSearch');
    const clearButton = document.getElementById('attendanceMemberSearchClear');
    const resultCount = document.getElementById('attendanceMemberResultCount');
    const rows = Array.from(document.querySelectorAll('#attendanceMonthlyTable tbody tr'));
    const totalRows = rows.length;

    function applyMemberSearch() {
        const query = (searchInput.value || '').toLowerCase().trim();
        let visibleRows = 0;

        rows.forEach(function (row) {
            const haystack = row.getAttribute('data-member-search') || '';
            const matched = query === '' || haystack.includes(query);
            row.style.display = matched ? '' : 'none';
            if (matched) visibleRows++;
        });

        resultCount.textContent = query
            ? visibleRows + ' of ' + totalRows + ' members'
            : totalRows + ' members';
    }

    searchInput.addEventListener('input', applyMemberSearch);

    clearButton.addEventListener('click', function () {
        searchInput.value = '';
        applyMemberSearch();
        searchInput.focus();
    });
});
</script>
@endsection
