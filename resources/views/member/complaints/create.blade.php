@extends('layouts.app')

@section('title', 'Submit Complaint / Suggestion')
@section('page_title', 'Submit Complaint / Suggestion')

@section('content')
<div class="member-complaints-shell">
    <section class="member-complaint-form-card">
        <div class="member-complaint-form-card__glow"></div>
        <div class="member-complaint-form-card__content">
            <div class="member-complaint-form-card__head">
                <div class="member-complaint-form-card__kicker">New Request</div>
                <h2 class="member-complaint-form-card__title">Submit Complaint / Suggestion</h2>
                <p class="member-complaint-form-card__subtitle">Share your issue, suggestion, or maintenance request with the support team.</p>
            </div>

            <form method="POST" action="{{ route('member.complaints.store') }}" class="member-complaint-form-grid">
                @csrf
                <div class="member-complaint-form-field">
                    <label class="member-complaint-form-label">Type</label>
                    <select name="type" class="form-select member-complaint-form-input" required>
                        @php($types = ['COMPLAINT' => 'Complaint', 'SUGGESTION' => 'Suggestion', 'MAINTENANCE_REQUEST' => 'Maintenance Request'])
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="member-complaint-form-field">
                    <label class="member-complaint-form-label">Category</label>
                    <select name="category" class="form-select member-complaint-form-input" required>
                        @php($categories = ['FOOD_QUALITY' => 'Food Quality', 'FOOD_QUANTITY' => 'Food Quantity', 'CLEANLINESS' => 'Cleanliness', 'STAFF_BEHAVIOR' => 'Staff Behavior', 'MENU_ISSUE' => 'Menu Issue', 'PAYMENT_BILL_ISSUE' => 'Payment/Bill Issue', 'ROOM_HOSTEL_ISSUE' => 'Room/Hostel Issue', 'WATER_ISSUE' => 'Water Issue',  'OTHER' => 'Other'])
                        @foreach($categories as $value => $label)
                            <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="member-complaint-form-field">
                    <label class="member-complaint-form-label">Priority</label>
                    <select name="priority" class="form-select member-complaint-form-input" required>
                        @foreach(['LOW' => 'Low', 'NORMAL' => 'Normal', 'HIGH' => 'High', 'URGENT' => 'Urgent'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('priority', 'NORMAL') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="member-complaint-form-field member-complaint-form-field--full">
                    <label class="member-complaint-form-label">Subject</label>
                    <input name="subject" class="form-control member-complaint-form-input" maxlength="255" value="{{ old('subject') }}" placeholder="Short complaint or suggestion subject" required>
                </div>
                <div class="member-complaint-form-field member-complaint-form-field--full">
                    <label class="member-complaint-form-label">Message</label>
                    <textarea name="message" class="form-control member-complaint-form-input member-complaint-form-textarea" rows="6" placeholder="Write full detail here" required>{{ old('message') }}</textarea>
                </div>
                <div class="member-complaint-form-field member-complaint-form-field--full">
                    <button class="btn member-complaint-form-submit">Submit</button>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection
