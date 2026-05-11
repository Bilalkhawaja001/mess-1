@extends('layouts.app')

@section('title', 'Menu')
@section('page_title', 'Menu')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
            <div class="col-md-3"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
            <div class="col-md-3"><select name="meal_type" class="form-select"><option value="">Meal Type</option>@foreach(['BREAKFAST','LUNCH','DINNER','TEA','OTHER'] as $v)<option value="{{ $v }}" @selected(request('meal_type')===$v)>{{ $v }}</option>@endforeach</select></div>
            <div class="col-md-3"><select name="status" class="form-select"><option value="">Status</option>@foreach(['DRAFT','APPROVED','INACTIVE'] as $v)<option value="{{ $v }}" @selected(request('status')===$v)>{{ $v }}</option>@endforeach</select></div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary btn-sm">Filter</button>
                @if(auth()->user()->hasPermission('menu.export'))
                <a class="btn btn-outline-success btn-sm" href="{{ route('admin.menu.export', request()->query()) }}">Export CSV</a>
                @endif
            </div>
        </form>
    </div>
</div>

@if(auth()->user()->hasPermission('menu.manage'))
<div class="card shadow-sm mb-3">
    <div class="card-header">Create Menu</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.menu.store') }}" class="row g-2">
            @csrf
            <div class="col-md-2"><input type="date" name="menu_date" class="form-control" required></div>
            <div class="col-md-2"><select name="meal_type" class="form-select" required>@foreach(['BREAKFAST','LUNCH','DINNER','TEA','OTHER'] as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach</select></div>
            <div class="col-md-3"><input name="title" class="form-control" placeholder="Title (optional)"></div>
            <div class="col-md-5"><input name="description" class="form-control" placeholder="Description"></div>
            <div class="col-12"><textarea name="items_text" class="form-control" rows="3" placeholder="Items" required></textarea></div>
            <div class="col-12"><input name="remarks" class="form-control" placeholder="Remarks"></div>
            <div class="col-12"><button class="btn btn-primary btn-sm">Create</button></div>
        </form>
    </div>
</div>
@endif

<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Date</th><th>Meal</th><th>Title</th><th>Items</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ optional($row->menu_date)->format('Y-m-d') }}</td>
                    <td>{{ $row->meal_type }}</td>
                    <td>{{ $row->title }}</td>
                    <td style="white-space: pre-line">{{ $row->items_text }}</td>
                    <td>{{ $row->status }}</td>
                    <td class="d-flex gap-1 flex-wrap">
                        @if(auth()->user()->hasPermission('menu.manage'))
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#menu-edit-{{ $row->id }}">
                                Edit
                            </button>
                        @endif
                        @if(auth()->user()->hasPermission('menu.approve') && $row->status !== 'APPROVED')
                        <form method="POST" action="{{ route('admin.menu.approve', $row) }}">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form>
                        @endif
                        @if(auth()->user()->hasPermission('menu.approve') && $row->status !== 'INACTIVE')
                        <form method="POST" action="{{ route('admin.menu.inactive', $row) }}">@csrf<button class="btn btn-sm btn-outline-danger">Inactive</button></form>
                        @endif
                    </td>
                </tr>
                @if(auth()->user()->hasPermission('menu.manage'))
                    <tr class="collapse bg-light" id="menu-edit-{{ $row->id }}">
                        <td colspan="6">
                            <form method="POST" action="{{ route('admin.menu.update', $row) }}" class="row g-2 align-items-end">
                                @csrf
                                @method('PUT')

                                <div class="col-md-2">
                                    <label class="form-label small mb-1">Date</label>
                                    <input type="date" name="menu_date" class="form-control form-control-sm" value="{{ optional($row->menu_date)->format('Y-m-d') }}" required>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label small mb-1">Meal Type</label>
                                    <select name="meal_type" class="form-select form-select-sm" required>
                                        @foreach(['BREAKFAST','LUNCH','DINNER','TEA','OTHER'] as $v)
                                            <option value="{{ $v }}" @selected($row->meal_type === $v)>{{ $v }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Title <span class="text-muted">(optional)</span></label>
                                    <input name="title" class="form-control form-control-sm" value="{{ $row->title }}" placeholder="Title (optional)">
                                </div>

                                <div class="col-md-5">
                                    <label class="form-label small mb-1">Description</label>
                                    <input name="description" class="form-control form-control-sm" value="{{ $row->description }}" placeholder="Description">
                                </div>

                                <div class="col-md-8">
                                    <label class="form-label small mb-1">Items</label>
                                    <textarea name="items_text" class="form-control form-control-sm" rows="3" required>{{ $row->items_text }}</textarea>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Remarks</label>
                                    <textarea name="remarks" class="form-control form-control-sm" rows="3">{{ $row->remarks }}</textarea>
                                </div>

                                <div class="col-12 d-flex gap-2 justify-content-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#menu-edit-{{ $row->id }}">Cancel</button>
                                    <button class="btn btn-sm btn-primary">Update Menu</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @endif
            @endforeach
            </tbody>
        </table>
        {{ $rows->links() }}
    </div>
</div>
@endsection
