@extends('layouts.app')

@section('title', 'Complaints / Suggestions')
@section('page_title', 'Complaints / Suggestions')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
            <div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
            <div class="col-md-2"><select name="status" class="form-select"><option value="">Status</option>@foreach(['OPEN','IN_PROGRESS','RESOLVED','CLOSED','REJECTED'] as $v)<option value="{{ $v }}" @selected(request('status')===$v)>{{ $v }}</option>@endforeach</select></div>
            <div class="col-md-2"><select name="type" class="form-select"><option value="">Type</option>@foreach(['COMPLAINT','SUGGESTION','MAINTENANCE_REQUEST'] as $v)<option value="{{ $v }}" @selected(request('type')===$v)>{{ $v }}</option>@endforeach</select></div>
            <div class="col-md-2"><select name="priority" class="form-select"><option value="">Priority</option>@foreach(['LOW','NORMAL','HIGH','URGENT'] as $v)<option value="{{ $v }}" @selected(request('priority')===$v)>{{ $v }}</option>@endforeach</select></div>
            <div class="col-md-2"><input name="category" value="{{ request('category') }}" class="form-control" placeholder="Category"></div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary btn-sm">Filter</button>
                @if(auth()->user()->hasPermission('complaint.export'))
                <a class="btn btn-outline-success btn-sm" href="{{ route('admin.complaints.export', request()->query()) }}">Export CSV</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>No</th><th>Date</th><th>By</th><th>Type</th><th>Category</th><th>Subject</th><th>Priority</th><th>Status</th><th>Assigned</th><th>Action</th></tr></thead>
            <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row->complaint_no }}</td>
                    <td>{{ optional($row->created_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ $row->member?->name ?? $row->user?->name ?? $row->submitted_by_name }}</td>
                    <td>{{ $row->type }}</td>
                    <td>{{ $row->category ?: '-' }}</td>
                    <td>{{ $row->subject }}</td>
                    <td>{{ $row->priority }}</td>
                    <td>{{ $row->status }}</td>
                    <td>{{ $row->assignee?->name ?? '-' }}</td>
                    <td>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.complaints.show', $row) }}">Open</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $rows->links() }}
    </div>
</div>
@endsection
