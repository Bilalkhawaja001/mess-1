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
            <thead><tr><th>No</th><th>Date</th><th>Type</th><th>Message</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->complaint_no }}</td>
                    <td>{{ optional($row->created_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ $row->type }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($row->message ?: $row->description, 80) }}</td>
                    <td>{{ $row->status }}</td>
                    <td><a class="btn btn-sm btn-outline-primary" href="{{ route('member.complaints.show', $row) }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">No complaints or suggestions submitted yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $rows->links() }}
    </div>
</div>
@endsection
