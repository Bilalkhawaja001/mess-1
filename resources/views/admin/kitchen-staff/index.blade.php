@extends('layouts.app')

@section('title', 'Kitchen Staff')
@section('page_title', 'Kitchen Staff Management')

@section('content')
@if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="card shadow-sm mb-3">
    <div class="card-header">Create Kitchen Staff</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.kitchen-staff.store') }}" class="row g-3" autocomplete="off">
            @csrf
            <div class="col-md-3"><input name="staff_code" class="form-control" placeholder="Staff code (e.g. KS-001)" value="{{ old('staff_code') }}" required></div>
            <div class="col-md-3"><input name="name" class="form-control" placeholder="Full name" value="{{ old('name') }}" required></div>
            <div class="col-md-3"><input name="username" class="form-control" placeholder="Username (app login)" value="{{ old('username') }}" autocomplete="off" required></div>
            <div class="col-md-3"><input type="password" name="password" class="form-control" placeholder="Password (min 8 chars)" autocomplete="new-password" required></div>
            <div class="col-md-3"><input name="mobile_number" class="form-control" placeholder="Mobile (optional)" value="{{ old('mobile_number') }}"></div>
            <div class="col-md-3 d-flex align-items-center">
                <div class="form-check me-3">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="ks_active">
                    <label class="form-check-label" for="ks_active">Active</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="can_view_outstanding" value="1" id="ks_out">
                    <label class="form-check-label" for="ks_out">Can view outstanding</label>
                </div>
            </div>
            <div class="col-md-3"><button class="btn btn-primary">Create</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Kitchen Staff List</div>
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Mobile</th>
                    <th class="text-center">PO</th>
                    <th class="text-center">GRN</th>
                    <th class="text-center">Outstanding</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($staff as $s)
                <tr>
                    <td>{{ $s->staff_code }}</td>
                    <td>{{ $s->name }}</td>
                    <td><code>{{ $s->username }}</code></td>
                    <td>{{ $s->mobile_number ?: '—' }}</td>
                    <td class="text-center">{{ $s->purchase_orders_count }}</td>
                    <td class="text-center">{{ $s->goods_receipts_count }}</td>
                    <td class="text-center">
                        @if($s->can_view_outstanding)
                            <span class="badge bg-info">Yes</span>
                        @else
                            <span class="badge bg-secondary">No</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($s->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editStaff{{ $s->id }}">Edit</button>
                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#pwStaff{{ $s->id }}">Reset Password</button>
                    </td>
                </tr>

                <div class="modal fade" id="editStaff{{ $s->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('admin.kitchen-staff.update', $s->id) }}" class="modal-content">
                            @csrf @method('PUT')
                            <div class="modal-header"><h5 class="modal-title">Edit {{ $s->staff_code }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body">
                                <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ $s->name }}" required></div>
                                <div class="mb-3"><label class="form-label">Mobile</label><input name="mobile_number" class="form-control" value="{{ $s->mobile_number }}"></div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="e_act{{ $s->id }}" @checked($s->is_active)>
                                    <label class="form-check-label" for="e_act{{ $s->id }}">Active</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="can_view_outstanding" value="1" id="e_out{{ $s->id }}" @checked($s->can_view_outstanding)>
                                    <label class="form-check-label" for="e_out{{ $s->id }}">Can view member outstanding</label>
                                </div>
                            </div>
                            <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
                        </form>
                    </div>
                </div>

                <div class="modal fade" id="pwStaff{{ $s->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('admin.kitchen-staff.reset-password', $s->id) }}" class="modal-content">
                            @csrf
                            <div class="modal-header"><h5 class="modal-title">Reset password — {{ $s->staff_code }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body">
                                <input type="password" name="password" class="form-control" placeholder="New password (min 8 chars)" required>
                                <div class="form-text">Staff will be logged out of the app immediately.</div>
                            </div>
                            <div class="modal-footer"><button class="btn btn-warning">Reset</button></div>
                        </form>
                    </div>
                </div>
            @empty
                <tr><td colspan="9" class="text-center text-muted">No kitchen staff yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
