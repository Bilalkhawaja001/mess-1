@extends('layouts.app')
@section('content')
<div class="container-fluid py-3">
<div class="d-flex justify-content-between align-items-center mb-3"><h1>Tyres Batteries</h1><a class="btn btn-primary" href="{{ route('admin.fleet.tyres-batteries.create') }}">Add</a></div>
<form class="row g-2 mb-3"><div class="col-md-4"><input name="search" class="form-control" value="{{ request('search') }}" placeholder="Search"></div><div class="col-md-2"><button class="btn btn-dark">Filter</button></div></form>
<div class="card"><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>ID</th><th>Status</th><th>Created</th><th class="text-end">Action</th></tr></thead><tbody>@foreach($records as $record)<tr><td>{{ $record->id }}</td><td><span class="badge bg-secondary">{{ $record->status ?? '-' }}</span></td><td>{{ optional($record->created_at)->format('Y-m-d') }}</td><td class="text-end"><a href="{{ route('admin.fleet.tyres-batteries.show', $record) }}" class="btn btn-sm btn-outline-primary">View</a></td></tr>@endforeach</tbody></table></div></div>
{{ $records->links() }}
</div>
@endsection
