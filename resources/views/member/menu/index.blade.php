@extends('layouts.app')

@section('title', 'Weekly Menu')
@section('page_title', 'Weekly Menu')

@section('content')
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
