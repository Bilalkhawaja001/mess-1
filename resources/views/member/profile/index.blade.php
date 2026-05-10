@extends('layouts.app')

@section('title', 'My Profile')
@section('page_title', 'My Profile')

@section('content')
<div class="member-profile-shell member-compact-stack member-section-gap">
    <section class="member-profile-card">
        <div class="member-profile-card__glow"></div>
        <div class="member-profile-card__content">
            <div class="member-profile-card__head">
                <div>
                    <div class="member-profile-card__kicker">Member Profile</div>
                    <h2 class="member-profile-card__title">Profile Information</h2>
                </div>
            </div>

            <div class="member-profile-card__grid">
                <div class="member-profile-card__item">
                    <span class="member-profile-card__label">Member Code</span>
                    <strong class="member-profile-card__value">{{ $member->member_code }}</strong>
                </div>
                <div class="member-profile-card__item">
                    <span class="member-profile-card__label">Name</span>
                    <strong class="member-profile-card__value">{{ $member->name }}</strong>
                </div>
                <div class="member-profile-card__item">
                    <span class="member-profile-card__label">Department</span>
                    <strong class="member-profile-card__value">{{ $member->department_name ?: '-' }}</strong>
                </div>
                <div class="member-profile-card__item">
                    <span class="member-profile-card__label">Account Status</span>
                    <strong class="member-profile-card__value">{{ $member->is_active ? 'Active' : 'Inactive' }}</strong>
                </div>
                <div class="member-profile-card__item">
                    <span class="member-profile-card__label">Mobile</span>
                    <strong class="member-profile-card__value">{{ $member->mobile_number ?: '-' }}</strong>
                </div>
                <div class="member-profile-card__item">
                    <span class="member-profile-card__label">Email</span>
                    <strong class="member-profile-card__value">{{ $user->email ?: '-' }}</strong>
                </div>
            </div>
        </div>
    </section>

    <section class="member-profile-form-card">
        <div class="member-profile-form-card__glow"></div>
        <div class="member-profile-form-card__content">
            <div class="member-profile-form-card__head">
                <div class="member-profile-form-card__kicker">Change Request</div>
                <h2 class="member-profile-form-card__title">Request Email / Mobile Change</h2>
                <p class="member-profile-form-card__subtitle">Submit a profile update request using the existing approval workflow.</p>
            </div>

            <form method="POST" action="{{ route('member.profile.change-requests.store') }}" class="member-profile-form-grid">
                @csrf
                <div class="member-profile-form-field">
                    <label class="member-profile-form-label">Field</label>
                    <select name="field_name" class="form-select member-profile-form-input" required>
                        <option value="email" @selected(old('field_name') === 'email')>Email</option>
                        <option value="mobile" @selected(old('field_name') === 'mobile')>Mobile</option>
                    </select>
                </div>
                <div class="member-profile-form-field member-profile-form-field--wide">
                    <label class="member-profile-form-label">New Value</label>
                    <input name="new_value" class="form-control member-profile-form-input" value="{{ old('new_value') }}" required>
                </div>
                <div class="member-profile-form-field member-profile-form-field--full">
                    <button class="btn member-profile-form-submit">Submit Change Request</button>
                </div>
            </form>
        </div>
    </section>

    <section class="member-profile-requests-wrap">
        <div class="member-profile-requests-wrap__head">
            <div>
                <div class="member-profile-requests-wrap__kicker">Activity</div>
                <h2 class="member-profile-requests-wrap__title">Recent Change Requests</h2>
            </div>
        </div>

        <div class="member-profile-requests-list">
            @forelse($changeRequests as $row)
                @php
                    $status = strtoupper((string) $row->status);
                    $statusClass = $status === 'APPROVED' ? 'success' : ($status === 'REJECTED' ? 'danger' : 'pending');
                @endphp
                <article class="member-profile-request-card">
                    <div class="member-profile-request-card__rail"></div>
                    <div class="member-profile-request-card__head">
                        <div>
                            <div class="member-profile-request-card__label">Date</div>
                            <div class="member-profile-request-card__value">{{ optional($row->created_at)->format('Y-m-d H:i') }}</div>
                        </div>
                        <div class="member-profile-status is-{{ $statusClass }}">{{ $row->status }}</div>
                    </div>

                    <div class="member-profile-request-card__grid">
                        <div class="member-profile-request-card__item">
                            <span class="member-profile-request-card__label">Field</span>
                            <strong class="member-profile-request-card__text">{{ strtoupper($row->field_name) }}</strong>
                        </div>
                        <div class="member-profile-request-card__item">
                            <span class="member-profile-request-card__label">Old Value</span>
                            <strong class="member-profile-request-card__text member-profile-request-card__text--break">{{ $row->old_value ?: '-' }}</strong>
                        </div>
                        <div class="member-profile-request-card__item">
                            <span class="member-profile-request-card__label">New Value</span>
                            <strong class="member-profile-request-card__text member-profile-request-card__text--break">{{ $row->new_value }}</strong>
                        </div>
                    </div>

                    <div class="member-profile-request-card__stack">
                        <div class="member-profile-request-card__item member-profile-request-card__item--full">
                            <span class="member-profile-request-card__label">Admin Remarks</span>
                            <strong class="member-profile-request-card__text member-profile-request-card__text--break">{{ $row->admin_remarks ?: '-' }}</strong>
                        </div>
                    </div>
                </article>
            @empty
                <div class="member-profile-empty">
                    <div class="member-profile-empty__icon"><i class="fas fa-id-card"></i></div>
                    <div class="member-profile-empty__title">No change requests found</div>
                    <p class="member-profile-empty__text">Your profile update requests will appear here with approval remarks.</p>
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
