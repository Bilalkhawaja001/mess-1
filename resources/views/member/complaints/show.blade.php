@extends('layouts.app')

@section('title', 'Complaint Detail')
@section('page_title', 'Complaint Detail')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6"><strong>No:</strong> {{ $complaint->complaint_no }}</div>
            <div class="col-md-6"><strong>Status:</strong> {{ $complaint->status }}</div>
            <div class="col-md-6"><strong>Type:</strong> {{ $complaint->type }}</div>
            <div class="col-md-6"><strong>Priority:</strong> {{ $complaint->priority }}</div>
            <div class="col-12"><strong>Subject:</strong> {{ $complaint->subject }}</div>
            <div class="col-12"><strong>Description:</strong><div class="mt-1">{{ $complaint->description }}</div></div>
            <div class="col-12"><strong>Admin Remarks:</strong><div class="mt-1">{{ $complaint->admin_remarks ?: '-' }}</div></div>
        </div>
    </div>
</div>
@endsection
