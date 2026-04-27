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
            <div class="col-md-3"><input name="title" class="form-control" placeholder="Title" required></div>
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
                        <form method="POST" action="{{ route('admin.menu.update', $row) }}" class="d-flex gap-1">
                            @csrf @method('PUT')
                            <input type="hidden" name="menu_date" value="{{ optional($row->menu_date)->format('Y-m-d') }}">
                            <input type="hidden" name="meal_type" value="{{ $row->meal_type }}">
                            <input type="hidden" name="title" value="{{ $row->title }}">
                            <input type="hidden" name="description" value="{{ $row->description }}">
                            <input type="hidden" name="items_text" value="{{ $row->items_text }}">
                            <input type="hidden" name="remarks" value="{{ $row->remarks }}">
                            <button class="btn btn-sm btn-outline-secondary">Save</button>
                        </form>
                        @endif
                        @if(auth()->user()->hasPermission('menu.approve') && $row->status !== 'APPROVED')
                        <form method="POST" action="{{ route('admin.menu.approve', $row) }}">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form>
                        @endif
                        @if(auth()->user()->hasPermission('menu.approve') && $row->status !== 'INACTIVE')
                        <form method="POST" action="{{ route('admin.menu.inactive', $row) }}">@csrf<button class="btn btn-sm btn-outline-danger">Inactive</button></form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $rows->links() }}
    </div>
</div>
@endsection
