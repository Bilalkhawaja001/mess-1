@extends('layouts.app')

@section('title', 'My Complaints / Suggestions')
@section('page_title', 'My Complaints / Suggestions')

@section('content')
<div class="member-module-screen">
    <div class="d-flex justify-content-end mb-3">
        <a class="btn btn-primary member-primary-btn" href="{{ route('member.complaints.create') }}">Submit New</a>
    </div>

    <section class="member-holo-card member-panel-card">
        <div class="member-panel-card__header">
            <div>
                <div class="member-section-title mb-1">Complaints & Suggestions</div>
                <div class="member-section-subtitle">Track all submitted complaints in compact mobile cards</div>
            </div>
        </div>
        <div class="member-ledger-cards">
            @forelse($rows as $row)
                <article class="member-holo-card member-data-card">
                    <div class="member-data-card__row">
                        <span class="member-data-card__label">Date</span>
                        <span class="member-data-card__value">{{ optional($row->created_at)->format('Y-m-d H:i') }}</span>
                    </div>
                    <div class="member-data-card__row">
                        <span class="member-data-card__label">Type</span>
                        <span class="member-data-card__value">{{ str_replace('_', ' ', $row->type) }}</span>
                    </div>
                    <div class="member-data-card__row">
                        <span class="member-data-card__label">Category</span>
                        <span class="member-data-card__value">{{ str_replace('_', ' ', $row->category ?? '-') }}</span>
                    </div>
                    <div class="member-data-card__row align-items-start">
                        <span class="member-data-card__label">Subject</span>
                        <span class="member-data-card__value member-data-card__value--wrap">{{ $row->subject }}</span>
                    </div>
                    <div class="member-data-card__grid">
                        <div>
                            <div class="member-data-card__label">Priority</div>
                            <div class="member-data-card__value">{{ $row->priority }}</div>
                        </div>
                        <div>
                            <div class="member-data-card__label">Status</div>
                            <div class="member-status-pill">{{ $row->status }}</div>
                        </div>
                    </div>
                    <a class="btn btn-outline-primary w-100 mt-3" href="{{ route('member.complaints.show', $row) }}">View</a>
                </article>
            @empty
                <div class="member-empty-card">No complaints or suggestions submitted yet.</div>
            @endforelse
        </div>
        <div class="pt-3">
            {{ $rows->links() }}
        </div>
    </section>
</div>
@endsection
