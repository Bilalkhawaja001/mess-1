@extends('layouts.app')

@section('title', 'Member Notifications')
@section('page_title', 'Member Notifications')

@section('content')
<div class="page-hero page-hero-compact mb-4">
    <div>
        <h1 class="page-hero-title">Member Notifications</h1>
        <div class="text-muted small">Send targeted alerts to member app devices and keep inbox history.</div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.announcements.store') }}" class="row g-3">
            @csrf

            <div class="col-md-4">
                <label class="form-label">Title</label>
                <input name="title" class="form-control" maxlength="160" value="{{ old('title') }}" placeholder="Notification title" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Severity</label>
                <select name="severity" class="form-select" required>
                    @foreach(['normal' => 'Normal', 'moderate' => 'Moderate', 'strict' => 'Strict', 'final' => 'Final'] as $key => $label)
                        <option value="{{ $key }}" @selected(old('severity', 'normal') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="form-text">Strict/Final will be handled with stronger Android alert behavior.</div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Target</label>
                <select name="target_scope" class="form-select" required>
                    <option value="all" @selected(old('target_scope', 'all') === 'all')>All members</option>
                    <option value="single" @selected(old('target_scope') === 'single')>Single member</option>
                    <option value="selected" @selected(old('target_scope') === 'selected')>Selected members</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Members for Single / Selected Target</label>
                <select name="member_ids[]" class="form-select" multiple size="8">
                    @foreach($memberOptions as $member)
                        <option value="{{ $member->id }}" @selected(in_array($member->id, old('member_ids', [])))>
                            {{ $member->member_code }} - {{ $member->name }}{{ $member->department_name ? ' / '.$member->department_name : '' }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">For all members, leave this empty. For single member, select exactly one.</div>
            </div>

            <div class="col-12">
                <label class="form-label">Message</label>
                <textarea name="message" class="form-control" maxlength="1000" rows="3" placeholder="Write notification message" required>{{ old('message') }}</textarea>
            </div>

            <div class="col-12 d-flex justify-content-end">
                <button class="btn btn-primary" type="submit" onclick="return confirm('Send this notification now?')">
                    Send Notification
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Notification History</div>
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Severity</th>
                    <th>Target</th>
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
                        <td class="fw-semibold text-uppercase">{{ $row->severity ?? 'normal' }}</td>
                        <td>{{ $row->target_type }}</td>
                        <td class="fw-semibold">{{ $row->title }}</td>
                        <td>{{ $row->message }}</td>
                        <td>{{ $row->total_tokens }}</td>
                        <td class="text-success fw-semibold">{{ $row->success_count }}</td>
                        <td class="text-danger fw-semibold">{{ $row->failed_count }}</td>
                        <td>{{ $row->sender?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">No notifications sent yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $rows->links() }}
    </div>
</div>
@endsection
