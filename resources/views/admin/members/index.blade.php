@extends('layouts.app')

@section('title', 'Members')
@section('page_title', 'Members Management')

@push('styles')
<style>
    .members-table {
        min-width: 1180px;
        font-size: 12px;
    }

    .members-table-wrap {
        overflow-x: auto;
        overflow-y: visible;
    }

    .members-table thead th {
        padding: 0.7rem 0.75rem;
        font-size: 11px;
        white-space: nowrap;
    }

    .members-table tbody td {
        padding: 0.55rem 0.75rem;
        font-size: 12px;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 160px;
        vertical-align: middle;
    }

    .members-status-col {
        width: 110px;
        min-width: 110px;
    }

    .members-actions-col {
        width: 88px;
        min-width: 88px;
    }

    .members-table tbody td.members-status-cell,
    .members-table tbody td.members-actions-cell {
        overflow: visible;
        text-overflow: clip;
        max-width: none;
    }

    .members-actions-dropdown {
        position: relative;
    }

    .members-actions-dropdown .btn {
        min-width: auto;
        padding: 0.3rem 0.55rem;
        font-size: 11px;
        border-radius: 10px;
        white-space: nowrap;
    }

    .members-actions-dropdown .dropdown-menu {
        min-width: 11rem;
        font-size: 12px;
        z-index: 1080;
    }

    .members-actions-dropdown .dropdown-item,
    .members-actions-dropdown .dropdown-item-text {
        font-size: 12px;
        white-space: nowrap;
    }

    .members-actions-dropdown .dropdown-item.text-danger {
        color: var(--bs-danger) !important;
    }

    .members-actions-dropdown .dropdown-item.text-warning {
        color: var(--bs-warning-text-emphasis, #997404) !important;
    }

    .members-edit-form .form-control,
    .members-edit-form .form-select {
        font-size: 12px;
        padding-top: 0.35rem;
        padding-bottom: 0.35rem;
    }

    @media (max-width: 991.98px) {
        .members-table-wrap {
            overflow-x: auto;
        }
    }
</style>
@endpush

@section('content')
<div class="mb-2 d-flex gap-2 flex-wrap">
    <a href="{{ route('admin.member-accounts.index') }}" class="btn btn-sm btn-outline-dark">Manage Member Portal Accounts</a>
    <a href="{{ route('admin.members.sample-csv') }}" class="btn btn-sm btn-outline-secondary">Download Sample CSV</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header">Bulk Import Members</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.members.import') }}" enctype="multipart/form-data" class="row g-2 align-items-center">
            @csrf
            <div class="col-md-6"><input type="file" name="file" class="form-control" accept=".csv,.txt" required></div>
            <div class="col-md-3"><button class="btn btn-outline-primary">Import CSV</button></div>
            <div class="col-12 text-muted small">Headers: member_code,name,department_name,mess_code,mobile_number,join_date,leave_date,username,is_active</div>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header">Create Member</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.members.store') }}" class="row g-3">
            @csrf
            <div class="col-md-2"><input name="member_code" class="form-control" placeholder="Member Code" required></div>
            <div class="col-md-3"><input name="name" class="form-control" placeholder="Name" required></div>
            <div class="col-md-2"><input name="department_name" class="form-control" placeholder="Department"></div>
            <div class="col-md-3">
                <select name="mess_id" class="form-select">
                    <option value="">Mess (optional)</option>
                    @foreach($messes as $mess)
                        <option value="{{ $mess->id }}">{{ $mess->name }} ({{ $mess->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><input name="mobile_number" class="form-control" placeholder="Mobile"></div>
            <div class="col-md-2"><input type="date" name="join_date" class="form-control" required></div>
            <div class="col-md-2"><input type="date" name="leave_date" class="form-control"></div>
            <div class="col-md-3">
                <select name="user_id" class="form-select">
                    <option value="">Linked User (optional)</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->username }} ({{ $u->role->code ?? '-' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="m_active">
                    <label class="form-check-label" for="m_active">Active</label>
                </div>
            </div>
            <div class="col-md-2"><button class="btn btn-primary">Create</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm members-page-card">
    <div class="card-header">Members List</div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.members.index') }}" class="row g-2 align-items-end mb-3">
            <div class="col-md-5">
                <label class="form-label">Search Member</label>
                <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control" placeholder="Search by member code, name, department, or mobile">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-outline-primary">Search</button>
                <a href="{{ route('admin.members.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
        <div class="table-wrap members-table-wrap">
        <table class="table table-sm align-middle members-table">
            <thead>
                <tr>
                    <th>Code</th><th>Name</th><th>Department</th><th>Mess</th><th>Mobile</th><th>Join</th><th>Leave</th><th>User</th><th class="members-status-col">Status</th><th class="members-actions-col text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $m)
                    <tr>
                        <td>{{ $m->member_code }}</td>
                        <td>{{ $m->name }}</td>
                        <td>{{ $m->department_name }}</td>
                        <td>{{ $m->mess->name ?? '—' }}</td>
                        <td>{{ $m->mobile_number ?? '-' }}</td>
                        <td>{{ optional($m->join_date)->format('Y-m-d') }}</td>
                        <td>{{ optional($m->leave_date)->format('Y-m-d') }}</td>
                        <td>{{ $m->user->username ?? '-' }}</td>
                        <td class="members-status-cell"><span class="badge {{ $m->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $m->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="members-actions-cell text-end">
                            <div class="dropdown members-actions-dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                                    Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li>
                                        <form method="POST" action="{{ route('admin.members.toggle-active', $m->id) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-warning">Toggle</button>
                                        </form>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item" data-bs-toggle="collapse" data-bs-target="#edit-member-{{ $m->id }}" aria-expanded="false" aria-controls="edit-member-{{ $m->id }}">Edit</button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('admin.members.remove', $m->id) }}" onsubmit="return confirm(@js($removalMeta[$m->id]['message'] ?? 'Are you sure?'))">
                                            @csrf
                                            <button type="submit" class="dropdown-item {{ ($removalMeta[$m->id]['can_delete'] ?? false) ? 'text-danger' : '' }}">
                                                {{ ($removalMeta[$m->id]['can_delete'] ?? false) ? 'Delete' : 'Remove' }}
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <tr class="collapse" id="edit-member-{{ $m->id }}">
                        <td colspan="10">
                            <form method="POST" action="{{ route('admin.members.update', $m->id) }}" class="row g-2 members-edit-form">
                                @csrf
                                @method('PUT')
                                <div class="col-md-2"><input name="member_code" class="form-control form-control-sm" value="{{ $m->member_code }}" required></div>
                                <div class="col-md-2"><input name="name" class="form-control form-control-sm" value="{{ $m->name }}" required></div>
                                <div class="col-md-2"><input name="department_name" class="form-control form-control-sm" value="{{ $m->department_name }}"></div>
                                <div class="col-md-2">
                                    <select name="mess_id" class="form-select form-select-sm">
                                        <option value="">No Mess</option>
                                        @foreach($messes as $mess)
                                            <option value="{{ $mess->id }}" @selected($m->mess_id === $mess->id)>{{ $mess->name }} ({{ $mess->code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2"><input name="mobile_number" class="form-control form-control-sm" value="{{ $m->mobile_number }}"></div>
                                <div class="col-md-2"><input type="date" name="join_date" class="form-control form-control-sm" value="{{ optional($m->join_date)->format('Y-m-d') }}" required></div>
                                <div class="col-md-2"><input type="date" name="leave_date" class="form-control form-control-sm" value="{{ optional($m->leave_date)->format('Y-m-d') }}"></div>
                                <div class="col-md-2">
                                    <select name="user_id" class="form-select form-select-sm">
                                        <option value="">No Link</option>
                                        @foreach($users as $u)
                                            <option value="{{ $u->id }}" @selected($m->user_id === $u->id)>{{ $u->username }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-1"><input type="checkbox" name="is_active" value="1" @checked($m->is_active)></div>
                                <div class="col-md-1"><button class="btn btn-sm btn-success">Save</button></div>
                            </form>
                            <div class="mt-3 pt-2 border-top">
                                <form method="POST" action="{{ route('admin.members.remove', $m->id) }}" onsubmit="return confirm(@js($removalMeta[$m->id]['message'] ?? 'Are you sure?'))">
                                    @csrf
                                    <button class="btn btn-sm {{ ($removalMeta[$m->id]['can_delete'] ?? false) ? 'btn-outline-danger' : 'btn-outline-secondary' }}">
                                        {{ ($removalMeta[$m->id]['can_delete'] ?? false) ? 'Permanently Delete Member' : 'Remove Member (Deactivate)' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
