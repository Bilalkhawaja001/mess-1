@extends('layouts.app')

@section('title', 'Weekly Menu')
@section('page_title', 'Weekly Menu')

@section('content')
@php($todayRow = collect($weekRows)->firstWhere('date', now()->format('Y-m-d')))
<div class="member-module-screen">
    <section class="member-holo-card member-panel-card mb-4">
        <div class="member-panel-card__header">
            <div>
                <div class="member-section-title mb-1">Today's Menu</div>
                <div class="member-section-subtitle">{{ now()->format('l, d M Y') }}</div>
            </div>
        </div>
        @if($todayRow)
            <div class="member-profile-grid">
                <div class="member-profile-tile"><span>Breakfast</span><strong>{{ $todayRow['BREAKFAST'] }}</strong></div>
                <div class="member-profile-tile"><span>Lunch</span><strong>{{ $todayRow['LUNCH'] }}</strong></div>
                <div class="member-profile-tile"><span>Dinner</span><strong>{{ $todayRow['DINNER'] }}</strong></div>
                <div class="member-profile-tile"><span>Tea / Other</span><strong>{{ $todayRow['TEA_OTHER'] }}</strong></div>
            </div>
        @else
            <div class="member-empty-card">No specific menu found for today.</div>
        @endif
    </section>

    <div class="member-week-nav mb-4">
        <a class="btn btn-outline-primary" href="{{ route('member.menu.index', ['week' => $prevWeek]) }}">Previous Week</a>
        <div class="member-week-nav__range">{{ $weekStart->format('Y-m-d') }} to {{ $weekEnd->format('Y-m-d') }}</div>
        <a class="btn btn-outline-primary" href="{{ route('member.menu.index', ['week' => $nextWeek]) }}">Next Week</a>
    </div>

    <section class="member-holo-card member-panel-card">
        <div class="member-panel-card__header">
            <div>
                <div class="member-section-title mb-1">Weekly Menu</div>
                <div class="member-section-subtitle">Daily meal planning in mobile card layout</div>
            </div>
        </div>
        <div class="member-ledger-cards">
            @foreach($weekRows as $row)
                <article class="member-holo-card member-data-card">
                    <div class="member-data-card__row"><span class="member-data-card__label">Day</span><span class="member-data-card__value">{{ $row['day'] }}</span></div>
                    <div class="member-data-card__row"><span class="member-data-card__label">Date</span><span class="member-data-card__value">{{ $row['date'] }}</span></div>
                    <div class="member-data-card__row align-items-start"><span class="member-data-card__label">Breakfast</span><span class="member-data-card__value member-data-card__value--wrap">{{ $row['BREAKFAST'] }}</span></div>
                    <div class="member-data-card__row align-items-start"><span class="member-data-card__label">Lunch</span><span class="member-data-card__value member-data-card__value--wrap">{{ $row['LUNCH'] }}</span></div>
                    <div class="member-data-card__row align-items-start"><span class="member-data-card__label">Dinner</span><span class="member-data-card__value member-data-card__value--wrap">{{ $row['DINNER'] }}</span></div>
                    <div class="member-data-card__row align-items-start"><span class="member-data-card__label">Tea / Other</span><span class="member-data-card__value member-data-card__value--wrap">{{ $row['TEA_OTHER'] }}</span></div>
                </article>
            @endforeach
        </div>
    </section>
</div>
@endsection
