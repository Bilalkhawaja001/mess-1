@extends('layouts.app')

@section('title', 'My Profile')
@section('page_title', 'My Profile')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">Profile Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><div class="text-muted small">Member Code</div><div class="fw-semibold">{{ $member->member_code }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Name</div><div class="fw-semibold">{{ $member->name }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Department</div><div class="fw-semibold">{{ $member->department_name ?: '-' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Account Status</div><div class="fw-semibold">{{ $member->is_active ? 'Active' : 'Inactive' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Mobile</div><div class="fw-semibold">{{ $member->mobile_number ?: '-' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Email</div><div class="fw-semibold">{{ $user->email ?: '-' }}</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">Request Email / Mobile Change</div>
            <div class="card-body">
                <form method="POST" action="{{ route('member.profile.change-requests.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Field</label>
                        <select name="field_name" class="form-select" required>
                            <option value="email" @selected(old('field_name') === 'email')>Email</option>
                            <option value="mobile" @selected(old('field_name') === 'mobile')>Mobile</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">New Value</label>
                        <input name="new_value" class="form-control" value="{{ old('new_value') }}" required>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Submit Change Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Recent Change Requests</div>
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle mb-0 member-mobile-table">
            <thead><tr><th>Date</th><th>Field</th><th>Old Value</th><th>New Value</th><th>Status</th><th>Admin Remarks</th></tr></thead>
            <tbody>
            @forelse($changeRequests as $row)
                <tr>
                    <td data-label="Date">{{ optional($row->created_at)->format('Y-m-d H:i') }}</td>
                    <td data-label="Field">{{ strtoupper($row->field_name) }}</td>
                    <td data-label="Old Value" class="member-mobile-wrap">{{ $row->old_value ?: '-' }}</td>
                    <td data-label="New Value" class="member-mobile-wrap">{{ $row->new_value }}</td>
                    <td data-label="Status" class="member-mobile-status"><span class="badge {{ $row->status === 'APPROVED' ? 'bg-success' : ($row->status === 'REJECTED' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ $row->status }}</span></td>
                    <td data-label="Admin Remarks" class="member-mobile-wrap">{{ $row->admin_remarks ?: '-' }}</td>
                </tr>
            @empty
                <tr><td data-label="Status" colspan="6" class="text-center text-muted member-mobile-wrap">No change requests found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
