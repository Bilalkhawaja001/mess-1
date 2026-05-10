@extends('layouts.app')

@section('title', 'My Complaints / Suggestions')
@section('page_title', 'My Complaints / Suggestions')

@section('content')
<div class="member-complaints-shell member-compact-stack member-section-gap">
    <section class="member-complaints-hero-card">
        <div class="member-complaints-hero-card__glow"></div>
        <div class="member-complaints-hero-card__content">
            <div>
                <div class="member-complaints-hero-card__kicker">Support Desk</div>
                <h2 class="member-complaints-hero-card__title">Complaint / Suggestion Tracker</h2>
                <p class="member-complaints-hero-card__subtitle">Review submitted items, monitor status updates, and raise a new request when needed.</p>
            </div>
            <a class="member-complaints-hero-card__action" href="{{ route('member.complaints.create') }}">New Complaint / Suggestion</a>
        </div>
    </section>

    <section class="member-complaints-list-wrap">
        <div class="member-complaints-list">
            @forelse($rows as $row)
                @php
                    $status = strtoupper((string) $row->status);
                    $statusClass = in_array($status, ['RESOLVED', 'APPROVED'])
                        ? 'success'
                        : (in_array($status, ['REJECTED', 'CLOSED'])
                            ? 'danger'
                            : (in_array($status, ['OPEN']) ? 'info' : 'pending'));
                @endphp
                <article class="member-complaint-card">
                    <div class="member-complaint-card__rail"></div>
                    <div class="member-complaint-card__head">
                        <div>
                            <div class="member-complaint-card__label">Date</div>
                            <div class="member-complaint-card__value">{{ optional($row->created_at)->format('Y-m-d H:i') }}</div>
                        </div>
                        <div class="member-complaint-status is-{{ $statusClass }}">{{ $row->status }}</div>
                    </div>

                    <div class="member-complaint-card__grid">
                        <div class="member-complaint-card__item">
                            <span class="member-complaint-card__label">Type</span>
                            <strong class="member-complaint-card__text">{{ str_replace('_', ' ', $row->type) }}</strong>
                        </div>
                        <div class="member-complaint-card__item">
                            <span class="member-complaint-card__label">Category</span>
                            <strong class="member-complaint-card__text">{{ str_replace('_', ' ', $row->category ?? '-') }}</strong>
                        </div>
                        <div class="member-complaint-card__item">
                            <span class="member-complaint-card__label">Priority</span>
                            <strong class="member-complaint-card__text">{{ $row->priority }}</strong>
                        </div>
                    </div>

                    <div class="member-complaint-card__stack">
                        <div class="member-complaint-card__item member-complaint-card__item--full">
                            <span class="member-complaint-card__label">Subject</span>
                            <strong class="member-complaint-card__text member-complaint-card__text--break">{{ $row->subject }}</strong>
                        </div>
                        <div class="member-complaint-card__action-row">
                            <a class="member-complaint-card__action" href="{{ route('member.complaints.show', $row) }}">View Details</a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="member-complaints-empty">
                    <div class="member-complaints-empty__icon"><i class="fas fa-comment-dots"></i></div>
                    <div class="member-complaints-empty__title">No complaints or suggestions yet</div>
                    <p class="member-complaints-empty__text">Your submitted support requests will appear here with priority and status updates.</p>
                </div>
            @endforelse
        </div>

        <div class="member-complaints-pagination">
            {{ $rows->links() }}
        </div>
    </section>
</div>
@endsection
