@extends('layouts.app')

@section('title', 'Complaint Detail')
@section('page_title', 'Complaint Detail')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6"><strong>No:</strong> {{ $complaint->complaint_no }}</div>
            <div class="col-md-6"><strong>Status:</strong> {{ $complaint->status }}</div>
            <div class="col-md-6"><strong>Type:</strong> {{ $complaint->type }}</div>
            <div class="col-md-6"><strong>Priority:</strong> {{ $complaint->priority }}</div>
            <div class="col-md-6"><strong>Submitted By:</strong> {{ $complaint->user?->name ?? $complaint->submitted_by_name }}</div>
            <div class="col-md-6"><strong>Assigned To:</strong> {{ $complaint->assignee?->name ?? '-' }}</div>
            <div class="col-12"><strong>Subject:</strong> {{ $complaint->subject }}</div>
            <div class="col-12"><strong>Description:</strong><div class="mt-1">{{ $complaint->description }}</div></div>
            <div class="col-12"><strong>Admin Remarks:</strong><div class="mt-1">{{ $complaint->admin_remarks ?: '-' }}</div></div>
        
              @if($complaint->attachments->isNotEmpty())
              <div class="col-12">
                  <strong>Attachments:</strong>
                  <div class="d-flex flex-wrap gap-2 mt-2">
                      @foreach($complaint->attachments as $attachment)
                          <a href="{{ asset('storage/'.$attachment->path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                              View Image {{ $loop->iteration }}
                          </a>
                      @endforeach
                  </div>
              </div>
              @endif
</div>
    </div>
</div>

@if(auth()->user()->hasPermission('complaint.manage'))
<div class="card shadow-sm">
    <div class="card-header">Update Status</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.complaints.status', $complaint) }}" class="row g-2">
            @csrf
            <div class="col-md-3"><select name="status" class="form-select" required>@foreach(['OPEN','IN_PROGRESS','RESOLVED','CLOSED','REJECTED'] as $v)<option value="{{ $v }}" @selected($complaint->status===$v)>{{ $v }}</option>@endforeach</select></div>
            <div class="col-md-3"><select name="assigned_to" class="form-select"><option value="">Assign To</option>@foreach(\App\Models\User::orderBy('name')->get() as $u)<option value="{{ $u->id }}" @selected($complaint->assigned_to===$u->id)>{{ $u->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><input name="admin_remarks" value="{{ $complaint->admin_remarks }}" class="form-control" placeholder="Internal remarks"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Save</button></div>
        </form>
    </div>
</div>
@endif
@endsection
