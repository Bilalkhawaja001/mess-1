@extends('layouts.app')

@section('title', 'Member Profile Change Requests')
@section('page_title', 'Member Profile Change Requests')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Status</option>
                    @foreach(['PENDING','APPROVED','REJECTED'] as $v)
                        <option value="{{ $v }}" @selected(request('status') === $v)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="field_name" class="form-select">
                    <option value="">Field</option>
                    @foreach(['email','mobile'] as $v)
                        <option value="{{ $v }}" @selected(request('field_name') === $v)>{{ strtoupper($v) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><button class="btn btn-primary btn-sm">Filter</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Request Date</th><th>Member</th><th>Field</th><th>Old Value</th><th>New Value</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ optional($row->created_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ $row->member?->member_code }} - {{ $row->member?->name }}</td>
                    <td>{{ strtoupper($row->field_name) }}</td>
                    <td>{{ $row->old_value ?: '-' }}</td>
                    <td>{{ $row->new_value }}</td>
                    <td><span class="badge {{ $row->status === 'APPROVED' ? 'bg-success' : ($row->status === 'REJECTED' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ $row->status }}</span></td>
                    <td>
                        @if($row->status === 'PENDING')
                            <form method="POST" action="{{ route('admin.member-profile-change-requests.update', $row) }}" class="d-flex flex-column gap-2">
                                @csrf
                                <select name="status" class="form-select form-select-sm" required>
                                    <option value="APPROVED">Approve</option>
                                    <option value="REJECTED">Reject</option>
                                </select>
                                <input name="admin_remarks" class="form-control form-control-sm" placeholder="Remarks (optional)">
                                <button class="btn btn-sm btn-outline-primary">Submit</button>
                            </form>
                        @else
                            <div class="small text-muted">{{ $row->admin_remarks ?: 'Processed' }}</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No profile change requests found.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $rows->links() }}
    </div>
</div>
@endsection
