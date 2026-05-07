@extends('layouts.app')

@section('title', 'Attendance')
@section('page_title', 'Attendance (Daily)')

@push('styles')
<style>
    .attendance-stat {
        border-radius: 18px;
        padding: 1rem 1.1rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        border: 1px solid #e2e8f0;
        height: 100%;
    }
    .attendance-stat .label { font-size: .74rem; text-transform: uppercase; letter-spacing: .08em; color: #64748b; font-weight: 800; }
    .attendance-stat .value { font-size: 1.45rem; font-weight: 800; color: #0f172a; }
    .attendance-table input[type="checkbox"] { width: 18px; height: 18px; }
</style>
@endpush

@section('content')
@php
    $memberCount = $members->count();
    $presentCount = 0;
    $mealChecks = 0;
    foreach ($members as $m) {
        $row = $existing->get($m->id);
        $b = $row?->breakfast ?? false;
        $l = $row?->lunch ?? false;
        $d = $row?->dinner ?? false;
        $p = $row?->present ?? ($b || $l || $d);
        if ($p) { $presentCount++; }
        $mealChecks += ($b ? 1 : 0) + ($l ? 1 : 0) + ($d ? 1 : 0);
    }
@endphp

<div class="hero-panel p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <h3 class="mb-2 fw-bold">Attendance</h3>
        </div>
        <div class="card border-0 shadow-sm" style="min-width: 240px;">
            <div class="card-body py-3">
                <div class="fw-semibold">{{ $selectedDate }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="attendance-stat"><div class="label">Members</div><div class="value">{{ $memberCount }}</div></div></div>
    <div class="col-md-4"><div class="attendance-stat"><div class="label">Present</div><div class="value">{{ $presentCount }}</div></div></div>
    <div class="col-md-4"><div class="attendance-stat"><div class="label">Meal Checks</div><div class="value">{{ $mealChecks }}</div></div></div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.attendance.index') }}" class="filters-bar row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="{{ $selectedDate }}">
            </div>
            <div class="col-md-2"><button class="btn btn-outline-primary">Load</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>Attendance</span>
    </div>
    <div class="card-body table-responsive">
        <form method="POST" action="{{ route('admin.attendance.store') }}">
            @csrf
            <input type="hidden" name="attendance_date" value="{{ $selectedDate }}">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" value="1" id="present_all" name="present_all">
                <label class="form-check-label" for="present_all">Present all (all meals)</label>
            </div>

            <div class="table-wrap">
                <table class="table table-sm align-middle attendance-table mb-0">
                    <thead>
                        <tr>
                            <th>Member Code</th><th>Name</th><th>Breakfast</th><th>Lunch</th><th>Dinner</th><th>Present</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($members as $idx => $m)
                            @php
                                $row = $existing->get($m->id);
                                $b = $row?->breakfast ?? false;
                                $l = $row?->lunch ?? false;
                                $d = $row?->dinner ?? false;
                                $p = $row?->present ?? ($b || $l || $d);
                            @endphp
                            <tr>
                                <td>{{ $m->member_code }}</td>
                                <td>{{ $m->name }}</td>
                                <td>
                                    <input type="hidden" name="attendance[{{ $idx }}][member_id]" value="{{ $m->id }}">
                                    <input type="checkbox" name="attendance[{{ $idx }}][breakfast]" value="1" @checked($b)>
                                </td>
                                <td><input type="checkbox" name="attendance[{{ $idx }}][lunch]" value="1" @checked($l)></td>
                                <td><input type="checkbox" name="attendance[{{ $idx }}][dinner]" value="1" @checked($d)></td>
                                <td><span class="badge {{ $p ? 'bg-success' : 'bg-secondary' }}">{{ $p ? 'Yes' : 'No' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <button class="btn btn-primary mt-3">Save Attendance</button>
        </form>
    </div>
</div>
@endsection
