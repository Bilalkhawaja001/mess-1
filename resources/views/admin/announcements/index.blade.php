@extends('layouts.app')

@section('title', 'Announcements')
@section('page_title', 'Announcements')

@section('content')
<div class="page-hero page-hero-compact mb-4">
    <div>
        <h1 class="page-hero-title">Announcements</h1>
        <div class="text-muted small">Send push notifications to registered member app devices.</div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.announcements.store') }}" class="row g-3">
            @csrf

            <div class="col-md-4">
                <label class="form-label">Title</label>
                <input name="title" class="form-control" maxlength="160" value="{{ old('title') }}" placeholder="Announcement" required>
            </div>

            <div class="col-md-8">
                <label class="form-label">Message</label>
                <input name="message" class="form-control" maxlength="1000" value="{{ old('message') }}" placeholder="Write announcement message" required>
            </div>

            <div class="col-12 d-flex justify-content-end">
                <button class="btn btn-primary" type="submit" onclick="return confirm('Send this announcement to all registered member devices?')">
                    Send to All Members
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Announcement History</div>
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Title</th>
                    <th>Message</th>
                    <th>Tokens</th>
                    <th>Success</th>
                    <th>Failed</th>
                    <th>Sent By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ optional($row->sent_at)->format('Y-m-d H:i') }}</td>
                        <td class="fw-semibold">{{ $row->title }}</td>
                        <td>{{ $row->message }}</td>
                        <td>{{ $row->total_tokens }}</td>
                        <td class="text-success fw-semibold">{{ $row->success_count }}</td>
                        <td class="text-danger fw-semibold">{{ $row->failed_count }}</td>
                        <td>{{ $row->sender?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No announcements sent yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $rows->links() }}
    </div>
</div>
@endsection
