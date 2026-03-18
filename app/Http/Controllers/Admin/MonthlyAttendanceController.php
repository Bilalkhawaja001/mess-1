<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\MonthlyAttendanceRequest;
use App\Models\Attendance;
use App\Models\Member;
use App\Models\MonthlyAttendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MonthlyAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $monthCycle = (string)$request->input('month_cycle', now()->format('Y-m'));
        $start = $monthCycle.'-01';
        $end = date('Y-m-t', strtotime($start));

        $members = Member::query()->where('is_active', true)->orderBy('member_code')->get();
        $snap = MonthlyAttendance::query()->where('month_cycle', $monthCycle)->get()->keyBy('member_id');

        $rows = $members->map(function($m) use ($start,$end,$snap){
            $present = Attendance::query()->where('member_id',$m->id)->whereBetween('attendance_date',[$start,$end])->where('present',true)->count();
            $stored = $snap->get($m->id);
            return [
                'member'=>$m,
                'present_days'=>$stored?->present_days ?? $present,
                'is_locked'=>$stored?->is_locked ?? false,
            ];
        });

        return view('admin.attendance_monthly.index', compact('monthCycle','rows'));
    }

    public function store(MonthlyAttendanceRequest $request): RedirectResponse
    {
        $monthCycle = $request->input('month_cycle');
        foreach ($request->input('rows', []) as $row) {
            $rec = MonthlyAttendance::query()->updateOrCreate(
                ['month_cycle'=>$monthCycle,'member_id'=>$row['member_id']],
                ['present_days'=>(int)$row['present_days']]
            );
            if ($request->boolean('approve')) {
                $rec->approved_by_user_id = Auth::id();
                $rec->approved_at = now();
                $rec->is_locked = true;
                $rec->save();
            }
        }

        return redirect()->route('admin.attendance-monthly.index', ['month_cycle'=>$monthCycle])->with('success', 'Monthly attendance saved.');
    }
}
