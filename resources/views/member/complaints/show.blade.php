@extends('layouts.app')

@section('title', 'Complaint / Suggestion Detail')
@section('page_title', 'Complaint / Suggestion Detail')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6"><strong>No:</strong> {{ $complaint->complaint_no }}</div>
            <div class="col-md-6"><strong>Status:</strong> {{ $complaint->status }}</div>
            <div class="col-md-6"><strong>Type:</strong> {{ $complaint->type }}</div>
            <div class="col-md-6"><strong>Date:</strong> {{ optional($complaint->created_at)->format('Y-m-d H:i') }}</div>
            <div class="col-12"><strong>Message:</strong><div class="mt-1">{{ $complaint->message ?: $complaint->description }}</div></div>
            <div class="col-12"><strong>Admin Remarks:</strong><div class="mt-1">{{ $complaint->admin_remarks ?: '-' }}</div></div>
        </div>
    </div>
</div>
@endsection
