@extends('layouts.app')

@section('title', 'Weekly Menu')
@section('page_title', 'Weekly Menu')

@section('content')
@php($todayRow = collect($weekRows)->firstWhere('date', now()->format('Y-m-d')))
<div class="member-menu-shell">
    <section class="member-menu-today-card">
        <div class="member-menu-today-card__glow"></div>
        <div class="member-menu-today-card__content">
            <div class="member-menu-today-card__head">
                <div>
                    <div class="member-menu-label">Today's Menu</div>
                    <h2 class="member-menu-today-card__title">{{ now()->format('l, Y-m-d') }}</h2>
                </div>
            </div>

            @if($todayRow)
                <div class="member-menu-today-grid">
                    <div class="member-menu-meal-card">
                        <div class="member-menu-label">Breakfast</div>
                        <div class="member-menu-meal-card__text" style="white-space: pre-line">{{ $todayRow['BREAKFAST'] }}</div>
                    </div>
                    <div class="member-menu-meal-card">
                        <div class="member-menu-label">Lunch</div>
                        <div class="member-menu-meal-card__text" style="white-space: pre-line">{{ $todayRow['LUNCH'] }}</div>
                    </div>
                    <div class="member-menu-meal-card">
                        <div class="member-menu-label">Dinner</div>
                        <div class="member-menu-meal-card__text" style="white-space: pre-line">{{ $todayRow['DINNER'] }}</div>
                    </div>
                    <div class="member-menu-meal-card">
                        <div class="member-menu-label">Tea / Other</div>
                        <div class="member-menu-meal-card__text" style="white-space: pre-line">{{ $todayRow['TEA_OTHER'] }}</div>
                    </div>
                </div>
            @else
                <div class="member-menu-empty">
                    <div class="member-menu-empty__icon"><i class="fas fa-utensils"></i></div>
                    <div class="member-menu-empty__title">No specific menu found for today</div>
                    <p class="member-menu-empty__text">Please check the weekly menu cards below for upcoming meal details.</p>
                </div>
            @endif
        </div>
    </section>

    <section class="member-menu-week-nav">
        <a class="member-menu-week-nav__btn" href="{{ route('member.menu.index', ['week' => $prevWeek]) }}">Previous Week</a>
        <div class="member-menu-week-nav__range">{{ $weekStart->format('Y-m-d') }} to {{ $weekEnd->format('Y-m-d') }}</div>
        <a class="member-menu-week-nav__btn" href="{{ route('member.menu.index', ['week' => $nextWeek]) }}">Next Week</a>
    </section>

    <section class="member-menu-week-list">
        @forelse($weekRows as $row)
            <article class="member-menu-week-card">
                <div class="member-menu-week-card__rail"></div>
                <div class="member-menu-week-card__head">
                    <div>
                        <div class="member-menu-label">Day</div>
                        <div class="member-menu-week-card__day">{{ $row['day'] }}</div>
                    </div>
                    <div class="member-menu-week-card__date">{{ $row['date'] }}</div>
                </div>

                <div class="member-menu-week-card__grid">
                    <div class="member-menu-week-card__item">
                        <span class="member-menu-label">Breakfast</span>
                        <div class="member-menu-week-card__text" style="white-space: pre-line">{{ $row['BREAKFAST'] }}</div>
                    </div>
                    <div class="member-menu-week-card__item">
                        <span class="member-menu-label">Lunch</span>
                        <div class="member-menu-week-card__text" style="white-space: pre-line">{{ $row['LUNCH'] }}</div>
                    </div>
                    <div class="member-menu-week-card__item">
                        <span class="member-menu-label">Dinner</span>
                        <div class="member-menu-week-card__text" style="white-space: pre-line">{{ $row['DINNER'] }}</div>
                    </div>
                    <div class="member-menu-week-card__item member-menu-week-card__item--full">
                        <span class="member-menu-label">Tea / Other</span>
                        <div class="member-menu-week-card__text" style="white-space: pre-line">{{ $row['TEA_OTHER'] }}</div>
                    </div>
                </div>
            </article>
        @empty
            <div class="member-menu-empty">
                <div class="member-menu-empty__icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="member-menu-empty__title">Menu not found</div>
                <p class="member-menu-empty__text">No weekly menu entries are available for this selected period.</p>
            </div>
        @endforelse
    </section>
</div>
@endsection
