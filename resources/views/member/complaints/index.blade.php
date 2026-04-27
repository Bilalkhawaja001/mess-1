@extends('layouts.app')

@section('title', 'My Complaints / Suggestions')
@section('page_title', 'My Complaints / Suggestions')

@section('content')
<div class="d-flex justify-content-end mb-3">
    <a class="btn btn-primary btn-sm" href="{{ route('member.complaints.create') }}">Submit New</a>
</div>
<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-sm">
            <thead><tr><th>No</th><th>Date</th><th>Type</th><th>Subject</th><th>Priority</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row->complaint_no }}</td>
                    <td>{{ optional($row->created_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ $row->type }}</td>
                    <td>{{ $row->subject }}</td>
                    <td>{{ $row->priority }}</td>
                    <td>{{ $row->status }}</td>
                    <td><a class="btn btn-sm btn-outline-primary" href="{{ route('member.complaints.show', $row) }}">View</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $rows->links() }}
    </div>
</div>
@endsection
