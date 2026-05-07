@extends('layouts.app')

@section('title', 'Submit Complaint / Suggestion')
@section('page_title', 'Submit Complaint / Suggestion')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('member.complaints.store') }}" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Type</label>
                <select name="type" class="form-select" required>
                    @php($types = ['COMPLAINT' => 'Complaint', 'SUGGESTION' => 'Suggestion', 'MAINTENANCE_REQUEST' => 'Maintenance Request'])
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Category</label>
                <select name="category" class="form-select" required>
                    @php($categories = ['FOOD_QUALITY' => 'Food Quality', 'FOOD_QUANTITY' => 'Food Quantity', 'CLEANLINESS' => 'Cleanliness', 'STAFF_BEHAVIOR' => 'Staff Behavior', 'MENU_ISSUE' => 'Menu Issue', 'PAYMENT_BILL_ISSUE' => 'Payment/Bill Issue', 'ROOM_HOSTEL_ISSUE' => 'Room/Hostel Issue', 'WATER_ISSUE' => 'Water Issue', 'ELECTRICITY_ISSUE' => 'Electricity Issue', 'OTHER' => 'Other'])
                    @foreach($categories as $value => $label)
                        <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Priority</label>
                <select name="priority" class="form-select" required>
                    @foreach(['LOW' => 'Low', 'NORMAL' => 'Normal', 'HIGH' => 'High', 'URGENT' => 'Urgent'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('priority', 'NORMAL') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Subject</label>
                <input name="subject" class="form-control" maxlength="255" value="{{ old('subject') }}" placeholder="Short complaint or suggestion subject" required>
            </div>
            <div class="col-12">
                <label class="form-label">Message</label>
                <textarea name="message" class="form-control" rows="6" placeholder="Write full detail here" required>{{ old('message') }}</textarea>
            </div>
            <div class="col-12"><button class="btn btn-primary">Submit</button></div>
        </form>
    </div>
</div>
@endsection
