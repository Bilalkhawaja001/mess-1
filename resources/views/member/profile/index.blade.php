@extends('layouts.app')

@section('title', 'My Profile')
@section('page_title', 'My Profile')

@section('content')
<div class="member-module-screen">
    <section class="member-holo-card member-panel-card mb-4">
        <div class="member-panel-card__header">
            <div>
                <div class="member-section-title mb-1">Profile Information</div>
                <div class="member-section-subtitle">Your linked member details</div>
            </div>
        </div>
        <div class="member-profile-grid">
            <div class="member-profile-tile"><span>Member Code</span><strong>{{ $member->member_code }}</strong></div>
            <div class="member-profile-tile"><span>Name</span><strong>{{ $member->name }}</strong></div>
            <div class="member-profile-tile"><span>Department</span><strong>{{ $member->department_name ?: '-' }}</strong></div>
            <div class="member-profile-tile"><span>Status</span><strong>{{ $member->is_active ? 'Active' : 'Inactive' }}</strong></div>
            <div class="member-profile-tile"><span>Mobile</span><strong>{{ $member->mobile_number ?: '-' }}</strong></div>
            <div class="member-profile-tile"><span>Email</span><strong>{{ $user->email ?: '-' }}</strong></div>
        </div>
    </section>

    <section class="member-holo-card member-panel-card mb-4">
        <div class="member-panel-card__header">
            <div>
                <div class="member-section-title mb-1">Request Email / Mobile Change</div>
                <div class="member-section-subtitle">Submit profile correction request</div>
            </div>
        </div>
        <div class="card-body pt-0">
            <form method="POST" action="{{ route('member.profile.change-requests.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label member-form-label">Field</label>
                    <select name="field_name" class="form-select" required>
                        <option value="email" @selected(old('field_name') === 'email')>Email</option>
                        <option value="mobile" @selected(old('field_name') === 'mobile')>Mobile</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label member-form-label">New Value</label>
                    <input name="new_value" class="form-control" value="{{ old('new_value') }}" required>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary member-primary-btn w-100">Submit Change Request</button>
                </div>
            </form>
        </div>
    </section>

    <section class="member-holo-card member-panel-card">
        <div class="member-panel-card__header">
            <div>
                <div class="member-section-title mb-1">Recent Change Requests</div>
                <div class="member-section-subtitle">Approval progress on your submitted profile updates</div>
            </div>
        </div>
        <div class="member-ledger-cards">
            @forelse($changeRequests as $row)
                <article class="member-holo-card member-data-card">
                    <div class="member-data-card__row"><span class="member-data-card__label">Date</span><span class="member-data-card__value">{{ optional($row->created_at)->format('Y-m-d H:i') }}</span></div>
                    <div class="member-data-card__row"><span class="member-data-card__label">Field</span><span class="member-data-card__value">{{ strtoupper($row->field_name) }}</span></div>
                    <div class="member-data-card__row align-items-start"><span class="member-data-card__label">Old Value</span><span class="member-data-card__value member-data-card__value--wrap">{{ $row->old_value ?: '-' }}</span></div>
                    <div class="member-data-card__row align-items-start"><span class="member-data-card__label">New Value</span><span class="member-data-card__value member-data-card__value--wrap">{{ $row->new_value }}</span></div>
                    <div class="member-data-card__row"><span class="member-data-card__label">Status</span><span class="member-status-pill">{{ $row->status }}</span></div>
                    <div class="member-data-card__row align-items-start"><span class="member-data-card__label">Admin Remarks</span><span class="member-data-card__value member-data-card__value--wrap">{{ $row->admin_remarks ?: '-' }}</span></div>
                </article>
            @empty
                <div class="member-empty-card">No change requests found.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
