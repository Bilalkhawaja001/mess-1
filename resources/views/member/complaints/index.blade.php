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
            <thead><tr><th>Date</th><th>Type</th><th>Category</th><th>Subject</th><th>Priority</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ optional($row->created_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ str_replace('_', ' ', $row->type) }}</td>
                    <td>{{ str_replace('_', ' ', $row->category ?? '-') }}</td>
                    <td>{{ $row->subject }}</td>
                    <td>{{ $row->priority }}</td>
                    <td>{{ $row->status }}</td>
                    <td><a class="btn btn-sm btn-outline-primary" href="{{ route('member.complaints.show', $row) }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No complaints or suggestions submitted yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $rows->links() }}
    </div>
</div>
@endsection
