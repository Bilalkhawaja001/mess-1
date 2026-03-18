<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StoreAttendanceRequest;
use App\Models\Attendance;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $selectedDate = $request->input('date', now()->toDateString());
        $dt = Carbon::parse($selectedDate)->toDateString();

        $members = Member::query()
            ->where('is_active', true)
            ->whereDate('join_date', '<=', $dt)
            ->where(function ($q) use ($dt) {
                $q->whereNull('leave_date')->orWhereDate('leave_date', '>=', $dt);
            })
            ->orderBy('member_code')
            ->get();

        $existing = Attendance::query()
            ->whereDate('attendance_date', $dt)
            ->get()
            ->keyBy('member_id');

        return view('admin.attendance.index', [
            'selectedDate' => $dt,
            'members' => $members,
            'existing' => $existing,
        ]);
    }

    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $dt = Carbon::parse($request->input('attendance_date'))->toDateString();
        $presentAll = $request->boolean('present_all', false);
        $rows = $request->input('attendance', []);

        foreach ($rows as $row) {
            $memberId = (int) ($row['member_id'] ?? 0);
            if ($memberId <= 0) {
                continue;
            }

            $breakfast = $presentAll ? true : ! empty($row['breakfast']);
            $lunch = $presentAll ? true : ! empty($row['lunch']);
            $dinner = $presentAll ? true : ! empty($row['dinner']);
            $present = $breakfast || $lunch || $dinner;

            Attendance::query()->updateOrCreate(
                [
                    'attendance_date' => $dt,
                    'member_id' => $memberId,
                ],
                [
                    'present' => $present,
                    'breakfast' => $breakfast,
                    'lunch' => $lunch,
                    'dinner' => $dinner,
                    'marked_by_user_id' => Auth::id(),
                ]
            );
        }

        return redirect()->route('admin.attendance.index', ['date' => $dt])->with('success', 'Attendance saved.');
    }
}
