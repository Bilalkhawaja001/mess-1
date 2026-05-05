@extends('layouts.app')

@section('title', 'Bills Download')
@section('page_title', 'Bills Download')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-0 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Month</label>
                <input class="form-control" type="month" name="month_cycle" value="{{ $monthCycle }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select class="form-select" name="department">
                    <option value="">All Departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) $departmentId === (string) $department->id)>{{ $department->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <div class="form-check mt-4 pt-2">
                    <input class="form-check-input" type="checkbox" name="group_by_department" value="1" id="group_by_department" @checked($groupByDepartment)>
                    <label class="form-check-label" for="group_by_department">Group by Department</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-check mt-4 pt-2">
                    <input class="form-check-input" type="checkbox" name="separate_files" value="1" id="separate_files" @checked($separateFiles)>
                    <label class="form-check-label" for="separate_files">Separate file per department</label>
                </div>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-primary">View Summary</button>
            </div>

            @if($monthCycle !== '')
                @php
                    $downloadQuery = [
                        'month_cycle' => $monthCycle,
                        'department' => $departmentId,
                        'group_by_department' => $groupByDepartment ? 1 : 0,
                        'separate_files' => $separateFiles ? 1 : 0,
                        'mess' => $messBucket,
                    ];
                @endphp
                <div class="col-md-3 d-grid">
                    <a class="btn btn-outline-success" href="{{ route('admin.reports.bills-download.export.csv', $downloadQuery) }}">Download Summary CSV</a>
                </div>
                <div class="col-md-3 d-grid">
                    <a class="btn btn-success" href="{{ route('admin.reports.bills-download.export.xlsx', $downloadQuery) }}">Download Summary Excel</a>
                </div>
                <div class="col-12 mt-2 d-flex gap-2 flex-wrap">
                    <a class="btn btn-outline-primary" href="{{ route('admin.reports.bills-download.export.xlsx', array_merge($downloadQuery, ['mess' => 'CENTRALIZED'])) }}">Centralize Mess Bill Download</a>
                    <a class="btn btn-outline-primary" href="{{ route('admin.reports.bills-download.export.xlsx', array_merge($downloadQuery, ['mess' => 'EXECUTIVE'])) }}">Executive Mess Bill Download</a>
                    <a class="btn btn-outline-primary" href="{{ route('admin.reports.bills-download.export.xlsx', array_merge($downloadQuery, ['mess' => 'CONTRACTOR'])) }}">Contractor Mess Bill Download</a>
                </div>
            @endif
        </form>
    </div>
</div>

@if(!empty($rows))
    <div class="card shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>EmployeeID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Mess</th>
                        <th>Days</th>
                        <th>Rate per Day</th>
                        <th>Current Bill</th>
                        <th>Previous</th>
                        <th>Payable</th>
                    </tr>
                </thead>
                <tbody>
                    @if($groupByDepartment)
                        @foreach($grouped as $group)
                            <tr class="table-secondary fw-bold">
                                <td colspan="10">{{ $group['department'] }} ({{ $group['totals']['member_count'] }} members)</td>
                            </tr>
                            @foreach($group['rows'] as $row)
                                <tr>
                                    <td>{{ $row['month_cycle'] }}</td>
                                    <td>{{ $row['member_id'] }}</td>
                                    <td>{{ $row['member_name'] }}</td>
                                    <td>{{ $row['department'] }}</td>
                                    <td>{{ $row['mess_name'] }}</td>
                                    <td>{{ $row['total_days'] }}</td>
                                    <td>{{ number_format((float) $row['rate_per_day'], 2) }}</td>
                                    <td>{{ number_format((float) $row['current_expenses'], 2) }}</td>
                                    <td>{{ number_format((float) $row['previous_balance'], 2) }}</td>
                                    <td>{{ number_format((float) $row['payable'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="table-light fw-bold">
                                <td>DEPT TOTAL</td>
                                <td colspan="4">{{ $group['department'] }}</td>
                                <td>{{ $group['totals']['total_days'] }}</td>
                                <td>{{ number_format((float) $group['totals']['rate_per_day'], 2) }}</td>
                                <td>{{ number_format((float) $group['totals']['current_expenses'], 2) }}</td>
                                <td>{{ number_format((float) $group['totals']['previous_balance'], 2) }}</td>
                                <td>{{ number_format((float) $group['totals']['payable'], 2) }}</td>
                            </tr>
                        @endforeach
                    @else
                        @foreach($rows as $row)
                            <tr>
                                <td>{{ $row['month_cycle'] }}</td>
                                <td>{{ $row['member_id'] }}</td>
                                <td>{{ $row['member_name'] }}</td>
                                <td>{{ $row['department'] }}</td>
                                <td>{{ $row['mess_name'] }}</td>
                                <td>{{ $row['total_days'] }}</td>
                                <td>{{ number_format((float) $row['rate_per_day'], 2) }}</td>
                                <td>{{ number_format((float) $row['current_expenses'], 2) }}</td>
                                <td>{{ number_format((float) $row['previous_balance'], 2) }}</td>
                                <td>{{ number_format((float) $row['payable'], 2) }}</td>
                            </tr>
                        @endforeach
                    @endif
                    <tr class="table-dark fw-bold">
                        <td>GRAND TOTAL</td>
                        <td colspan="4"></td>
                        <td>{{ $totals['total_days'] }}</td>
                        <td>{{ number_format((float) $totals['rate_per_day'], 2) }}</td>
                        <td>{{ number_format((float) $totals['current_expenses'], 2) }}</td>
                        <td>{{ number_format((float) $totals['previous_balance'], 2) }}</td>
                        <td>{{ number_format((float) $totals['payable'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
