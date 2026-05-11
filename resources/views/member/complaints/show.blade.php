@extends('layouts.app')

@section('title', 'Complaint / Suggestion Detail')
@section('page_title', 'Complaint / Suggestion Detail')

@section('content')
@php
    $status = strtoupper((string) $complaint->status);
    $statusClass = in_array($status, ['RESOLVED', 'APPROVED'])
        ? 'success'
        : (in_array($status, ['REJECTED', 'CLOSED'])
            ? 'danger'
            : (in_array($status, ['OPEN']) ? 'info' : 'pending'));
@endphp

<div class="member-complaints-shell member-compact-stack member-section-gap">
    <section class="member-complaint-detail-card">
        <div class="member-complaint-detail-card__glow"></div>
        <div class="member-complaint-detail-card__content">
            <div class="member-complaint-detail-card__head">
                <div>
                    <div class="member-complaint-detail-card__kicker">Support Ticket</div>
                    <h2 class="member-complaint-detail-card__title">Complaint / Suggestion Detail</h2>
                </div>
                <div class="member-complaint-status is-{{ $statusClass }}">{{ $complaint->status }}</div>
            </div>

            <div class="member-complaint-detail-card__grid">
                <div class="member-complaint-detail-card__item">
                    <span class="member-complaint-detail-card__label">Complaint No</span>
                    <strong class="member-complaint-detail-card__value">{{ $complaint->complaint_no }}</strong>
                </div>
                <div class="member-complaint-detail-card__item">
                    <span class="member-complaint-detail-card__label">Type</span>
                    <strong class="member-complaint-detail-card__value">{{ str_replace('_', ' ', $complaint->type) }}</strong>
                </div>
                <div class="member-complaint-detail-card__item">
                    <span class="member-complaint-detail-card__label">Category</span>
                    <strong class="member-complaint-detail-card__value">{{ str_replace('_', ' ', $complaint->category ?? '-') }}</strong>
                </div>
                <div class="member-complaint-detail-card__item">
                    <span class="member-complaint-detail-card__label">Priority</span>
                    <strong class="member-complaint-detail-card__value">{{ $complaint->priority }}</strong>
                </div>
                <div class="member-complaint-detail-card__item member-complaint-detail-card__item--wide">
                    <span class="member-complaint-detail-card__label">Date</span>
                    <strong class="member-complaint-detail-card__value">{{ optional($complaint->created_at)->format('Y-m-d H:i') }}</strong>
                </div>
            </div>

            <div class="member-complaint-detail-card__stack">
                <div class="member-complaint-detail-card__block">
                    <span class="member-complaint-detail-card__label">Subject</span>
                    <div class="member-complaint-detail-card__text">{{ $complaint->subject }}</div>
                </div>
                <div class="member-complaint-detail-card__block">
                    <span class="member-complaint-detail-card__label">Message</span>
                    <div class="member-complaint-detail-card__text">{{ $complaint->message ?: $complaint->description }}</div>
                </div>
                <div class="member-complaint-detail-card__block">
                    <span class="member-complaint-detail-card__label">Admin Remarks / Response</span>
                    <div class="member-complaint-detail-card__text">{{ $complaint->admin_remarks ?: '-' }}</div>
                </div>
            </div>
        </div>
    </section>
</div>

    @if($complaint->attachments->isNotEmpty())
        <section class="member-complaint-detail-card mt-3">
            <div class="member-complaint-detail-card__content">
                <div class="member-complaint-detail-card__block">
                    <span class="member-complaint-detail-card__label">Attachments</span>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @foreach($complaint->attachments as $attachment)
                            <a href="{{ asset('storage/'.$attachment->path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                View Image {{ $loop->iteration }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

@endsection
