@extends('layouts.member-app')

@section('title', 'Menu')
@section('app_title', 'Weekly Menu')

@section('content')
    <section class="app-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a class="btn btn-sm btn-outline-primary" href="{{ route('member.app.menu.index', ['week' => $prevWeek]) }}">Previous</a>
            <strong>{{ $weekStart->format('d M') }} - {{ $weekEnd->format('d M Y') }}</strong>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('member.app.menu.index', ['week' => $nextWeek]) }}">Next</a>
        </div>
        <div class="app-list">
            @foreach($weekRows as $day)
                <div class="app-list-item">
                    <h3>{{ $day['day'] }} <span class="muted">{{ $day['date'] }}</span></h3>
                    @foreach(['BREAKFAST','LUNCH','DINNER','TEA_OTHER'] as $meal)
                        <div class="mt-2"><strong>{{ str_replace('_', ' / ', $meal) }}</strong><div class="muted" style="white-space:pre-line">{{ $day[$meal] ?? '-' }}</div></div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </section>
@endsection
