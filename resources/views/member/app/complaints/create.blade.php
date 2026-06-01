@extends('layouts.member-app')

@section('title', 'New Complaint')
@section('app_title', 'New Complaint')

@section('content')
    <section class="app-card">
        <form method="POST" action="{{ route('member.app.complaints.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3"><label class="form-label">Type</label><select class="form-select" name="type" required><option value="COMPLAINT">Complaint</option><option value="SUGGESTION">Suggestion</option><option value="MAINTENANCE_REQUEST">Maintenance Request</option></select></div>
            <div class="mb-3"><label class="form-label">Category</label><select class="form-select" name="category" required>@foreach(['FOOD_QUALITY','FOOD_QUANTITY','CLEANLINESS','STAFF_BEHAVIOR','MENU_ISSUE','PAYMENT_BILL_ISSUE','WATER_ISSUE','OTHER'] as $category)<option value="{{ $category }}">{{ str_replace('_',' ', $category) }}</option>@endforeach</select></div>
            <div class="mb-3"><label class="form-label">Priority</label><select class="form-select" name="priority" required>@foreach(['LOW','NORMAL','HIGH','URGENT'] as $priority)<option value="{{ $priority }}">{{ $priority }}</option>@endforeach</select></div>
            <div class="mb-3"><label class="form-label">Subject</label><input class="form-control" name="subject" value="{{ old('subject') }}" required></div>
            <div class="mb-3"><label class="form-label">Message</label><textarea class="form-control" name="message" rows="5" required>{{ old('message') }}</textarea></div>
            <button class="btn btn-primary w-100" type="submit">Submit</button>
        </form>
    </section>
@endsection
