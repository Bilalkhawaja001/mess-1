@extends('layouts.app')

@section('title', 'Attendance')
@section('page_title', 'Attendance (Daily)')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.attendance.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="{{ $selectedDate }}">
            </div>
            <div class="col-md-2"><button class="btn btn-outline-primary">Load</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Attendance Table</span>
        <small class="text-muted">Flask parity: breakfast/lunch/dinner => present</small>
    </div>
    <div class="card-body table-responsive">
        <form method="POST" action="{{ route('admin.attendance.store') }}">
            @csrf
            <input type="hidden" name="attendance_date" value="{{ $selectedDate }}">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" value="1" id="present_all" name="present_all">
                <label class="form-check-label" for="present_all">Present all (all meals)</label>
            </div>

            <table class="table table-sm align-middle">
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

            <button class="btn btn-primary">Save Attendance</button>
        </form>
    </div>
</div>
@endsection
