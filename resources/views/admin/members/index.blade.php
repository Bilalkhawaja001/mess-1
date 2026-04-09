@extends('layouts.app')

@section('title', 'Members')
@section('page_title', 'Members Management')

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

<div class="card shadow-sm">
    <div class="card-header">Members List</div>
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Code</th><th>Name</th><th>Department</th><th>Mess</th><th>Mobile</th><th>Join</th><th>Leave</th><th>User</th><th>Status</th><th>Actions</th>
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
                        <td><span class="badge {{ $m->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $m->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="d-flex gap-2 flex-wrap">
                            <form method="POST" action="{{ route('admin.members.toggle-active', $m->id) }}">@csrf<button class="btn btn-sm btn-outline-warning">Toggle</button></form>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-member-{{ $m->id }}">Edit</button>
                            <form method="POST" action="{{ route('admin.members.remove', $m->id) }}" onsubmit="return confirm(@js($removalMeta[$m->id]['message'] ?? 'Are you sure?'))">
                                @csrf
                                <button class="btn btn-sm {{ ($removalMeta[$m->id]['can_delete'] ?? false) ? 'btn-outline-danger' : 'btn-outline-secondary' }}">
                                    {{ ($removalMeta[$m->id]['can_delete'] ?? false) ? 'Delete' : 'Remove' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    <tr class="collapse" id="edit-member-{{ $m->id }}">
                        <td colspan="10">
                            <form method="POST" action="{{ route('admin.members.update', $m->id) }}" class="row g-2">
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
                                <div class="col-md-3">
                                    <form method="POST" action="{{ route('admin.members.remove', $m->id) }}" onsubmit="return confirm(@js($removalMeta[$m->id]['message'] ?? 'Are you sure?'))">
                                        @csrf
                                        <button class="btn btn-sm {{ ($removalMeta[$m->id]['can_delete'] ?? false) ? 'btn-outline-danger' : 'btn-outline-secondary' }} w-100">
                                            {{ ($removalMeta[$m->id]['can_delete'] ?? false) ? 'Permanently Delete Member' : 'Remove Member (Deactivate)' }}
                                        </button>
                                    </form>
                                </div>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
