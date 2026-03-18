@extends('layouts.app')

@section('title', 'Users')
@section('page_title', 'Users Management')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-header">Create User</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.users.store') }}" class="row g-3">
            @csrf
            <div class="col-md-3"><input name="username" class="form-control" placeholder="Username" required></div>
            <div class="col-md-3"><input name="name" class="form-control" placeholder="Full name" required></div>
            <div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="Email (optional)"></div>
            <div class="col-md-3"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
            <div class="col-md-3">
                <select name="role_id" class="form-select" required>
                    <option value="">Role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }} ({{ $role->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="u_active">
                    <label class="form-check-label" for="u_active">Active</label>
                </div>
            </div>
            <div class="col-md-3"><button class="btn btn-primary">Create</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Users List</div>
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead>
            <tr>
                <th>ID</th><th>Username</th><th>Name</th><th>Role</th><th>Status</th><th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @foreach($rows as $u)
                <tr>
                    <td>{{ $u->id }}</td>
                    <td>{{ $u->username }}</td>
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->role->code ?? '-' }}</td>
                    <td>
                        <span class="badge {{ $u->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $u->is_active ? 'Active' : 'Inactive' }}</span>
                    </td>
                    <td class="d-flex gap-2 flex-wrap">
                        <form method="POST" action="{{ route('admin.users.toggle-active', $u->id) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-warning">Toggle</button>
                        </form>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-user-{{ $u->id }}">Edit</button>
                    </td>
                </tr>
                <tr class="collapse" id="edit-user-{{ $u->id }}">
                    <td colspan="6">
                        <form method="POST" action="{{ route('admin.users.update', $u->id) }}" class="row g-2">
                            @csrf
                            @method('PUT')
                            <div class="col-md-2"><input name="username" class="form-control form-control-sm" value="{{ $u->username }}" required></div>
                            <div class="col-md-2"><input name="name" class="form-control form-control-sm" value="{{ $u->name }}" required></div>
                            <div class="col-md-2"><input name="email" class="form-control form-control-sm" value="{{ $u->email }}"></div>
                            <div class="col-md-2"><input name="password" type="password" class="form-control form-control-sm" placeholder="New password (optional)"></div>
                            <div class="col-md-2">
                                <select name="role_id" class="form-select form-select-sm" required>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" @selected($u->role_id === $role->id)>{{ $role->code }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-1 d-flex align-items-center">
                                <input type="checkbox" name="is_active" value="1" @checked($u->is_active)>
                            </div>
                            <div class="col-md-1"><button class="btn btn-sm btn-success">Save</button></div>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
