@extends('layouts.app')

@section('title', 'Weekly Menu')
@section('page_title', 'Weekly Menu')

@section('content')
@php($todayRow = collect($weekRows)->firstWhere('date', now()->format('Y-m-d')))
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <div class="text-muted small">Today's Menu</div>
                <div class="fw-semibold">{{ now()->format('l, Y-m-d') }}</div>
            </div>
        </div>
        @if($todayRow)
            <div class="row g-3 mt-1">
                <div class="col-md-3"><div class="border rounded p-2 h-100"><div class="small text-muted">Breakfast</div><div style="white-space: pre-line">{{ $todayRow['BREAKFAST'] }}</div></div></div>
                <div class="col-md-3"><div class="border rounded p-2 h-100"><div class="small text-muted">Lunch</div><div style="white-space: pre-line">{{ $todayRow['LUNCH'] }}</div></div></div>
                <div class="col-md-3"><div class="border rounded p-2 h-100"><div class="small text-muted">Dinner</div><div style="white-space: pre-line">{{ $todayRow['DINNER'] }}</div></div></div>
                <div class="col-md-3"><div class="border rounded p-2 h-100"><div class="small text-muted">Tea / Other</div><div style="white-space: pre-line">{{ $todayRow['TEA_OTHER'] }}</div></div></div>
            </div>
        @else
            <div class="text-muted mt-2">No specific menu found for today.</div>
        @endif
    </div>
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('member.menu.index', ['week' => $prevWeek]) }}">Previous Week</a>
    <div class="fw-semibold">{{ $weekStart->format('Y-m-d') }} to {{ $weekEnd->format('Y-m-d') }}</div>
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('member.menu.index', ['week' => $nextWeek]) }}">Next Week</a>
</div>

<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Date</th>
                    <th>Breakfast</th>
                    <th>Lunch</th>
                    <th>Dinner</th>
                    <th>Tea/Other</th>
                </tr>
            </thead>
            <tbody>
                @foreach($weekRows as $row)
                <tr>
                    <td>{{ $row['day'] }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td style="white-space: pre-line">{{ $row['BREAKFAST'] }}</td>
                    <td style="white-space: pre-line">{{ $row['LUNCH'] }}</td>
                    <td style="white-space: pre-line">{{ $row['DINNER'] }}</td>
                    <td style="white-space: pre-line">{{ $row['TEA_OTHER'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
