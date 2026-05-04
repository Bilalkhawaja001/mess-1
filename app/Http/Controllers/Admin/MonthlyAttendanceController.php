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
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonthlyAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $monthCycle = (string) $request->input('month_cycle', now()->format('Y-m'));
        $start = $monthCycle.'-01';
        $end = date('Y-m-t', strtotime($start));

        $members = Member::query()->where('is_active', true)->orderBy('member_code')->get();
        $snap = MonthlyAttendance::query()->where('month_cycle', $monthCycle)->get()->keyBy('member_id');

        $rows = $members->map(function ($m) use ($start, $end, $snap) {
            $present = Attendance::query()->where('member_id', $m->id)->whereBetween('attendance_date', [$start, $end])->where('present', true)->count();
            $stored = $snap->get($m->id);

            return [
                'member' => $m,
                'present_days' => $stored?->present_days ?? $present,
                'is_locked' => $stored?->is_locked ?? false,
                'approved_at' => $stored?->approved_at,
            ];
        });

        return view('admin.attendance_monthly.index', compact('monthCycle', 'rows'));
    }

    public function store(MonthlyAttendanceRequest $request): RedirectResponse
    {
        $monthCycle = $request->input('month_cycle');
        foreach ($request->input('rows', []) as $row) {
            $rec = MonthlyAttendance::query()->updateOrCreate(
                ['month_cycle' => $monthCycle, 'member_id' => $row['member_id']],
                ['present_days' => (int) $row['present_days']]
            );
            if ($request->boolean('approve')) {
                $rec->approved_by_user_id = Auth::id();
                $rec->approved_at = now();
                $rec->is_locked = true;
                $rec->save();
            }
        }

        return redirect()->route('admin.attendance-monthly.index', ['month_cycle' => $monthCycle])->with('success', 'Monthly attendance saved.');
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['month_cycle', 'member_code', 'present_days']);
            fputcsv($out, ['2026-04', '10001', '26']);
            fclose($out);
        }, 'monthly_attendance_template.csv', ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        if (! $handle) {
            return back()->with('error', 'Unable to read uploaded CSV file.');
        }

        $header = fgetcsv($handle);
        $expected = ['month_cycle', 'member_code', 'present_days'];
        $normalized = array_map(fn ($v) => strtolower(trim((string) $v)), $header ?: []);
        if ($normalized !== $expected) {
            fclose($handle);
            return back()->with('error', 'Invalid CSV header. Required columns: month_cycle, member_code, present_days');
        }

        $members = Member::query()->select('id', 'member_code')->get()->keyBy('member_code');
        $errors = [];
        $payload = [];
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if ($row === [null] || $row === false) {
                continue;
            }

            $monthCycle = trim((string) ($row[0] ?? ''));
            $memberCode = trim((string) ($row[1] ?? ''));
            $presentRaw = trim((string) ($row[2] ?? ''));

            if ($monthCycle === '' && $memberCode === '' && $presentRaw === '') {
                continue;
            }

            if (! preg_match('/^\d{4}-\d{2}$/', $monthCycle)) {
                $errors[] = "Line {$line}: invalid month_cycle '{$monthCycle}' (expected YYYY-MM).";
                continue;
            }

            $member = $members->get($memberCode);
            if (! $member) {
                $errors[] = "Line {$line}: member_code '{$memberCode}' not found.";
                continue;
            }

            if ($presentRaw === '' || ! preg_match('/^\d+$/', $presentRaw)) {
                $errors[] = "Line {$line}: present_days must be a non-negative integer.";
                continue;
            }

            $presentDays = (int) $presentRaw;
            if ($presentDays < 0) {
                $errors[] = "Line {$line}: present_days must be >= 0.";
                continue;
            }

            $payload[] = [
                'month_cycle' => $monthCycle,
                'member_id' => (int) $member->id,
                'present_days' => $presentDays,
            ];
        }
        fclose($handle);

        if ($errors) {
            $errors = array_slice($errors, 0, 20);
            return back()->with('error', "CSV import rejected:\n".implode("\n", $errors));
        }

        $imported = 0;
        $updated = 0;

        DB::transaction(function () use ($payload, &$imported, &$updated) {
            foreach ($payload as $row) {
                $existing = MonthlyAttendance::query()
                    ->where('month_cycle', $row['month_cycle'])
                    ->where('member_id', $row['member_id'])
                    ->first();

                if ($existing) {
                    $existing->present_days = $row['present_days'];
                    $existing->save();
                    $updated++;
                    continue;
                }

                MonthlyAttendance::query()->create([
                    'month_cycle' => $row['month_cycle'],
                    'member_id' => $row['member_id'],
                    'present_days' => $row['present_days'],
                    'approved_by_user_id' => null,
                    'approved_at' => null,
                    'is_locked' => false,
                ]);
                $imported++;
            }
        });

        return redirect()->route('admin.attendance-monthly.index', ['month_cycle' => $payload[0]['month_cycle'] ?? now()->format('Y-m')])
            ->with('success', "Monthly attendance CSV imported successfully. Imported={$imported}, Updated={$updated}");
    }

    public function approve(Request $request): RedirectResponse
    {
        $monthCycle = (string) $request->input('month_cycle', now()->format('Y-m'));

        MonthlyAttendance::query()
            ->where('month_cycle', $monthCycle)
            ->update([
                'approved_by_user_id' => Auth::id(),
                'approved_at' => now(),
                'is_locked' => true,
            ]);

        return redirect()->route('admin.attendance-monthly.index', ['month_cycle' => $monthCycle])->with('success', 'Monthly attendance approved & locked.');
    }

    public function unlock(Request $request): RedirectResponse
    {
        $monthCycle = (string) $request->input('month_cycle', now()->format('Y-m'));

        MonthlyAttendance::query()
            ->where('month_cycle', $monthCycle)
            ->update([
                'is_locked' => false,
                'approved_by_user_id' => null,
                'approved_at' => null,
            ]);

        return redirect()->route('admin.attendance-monthly.index', ['month_cycle' => $monthCycle])->with('success', 'Monthly attendance unlocked.');
    }

    public function export(Request $request): StreamedResponse
    {
        $monthCycle = (string) $request->input('month_cycle', now()->format('Y-m'));

        $rows = MonthlyAttendance::query()
            ->with('member')
            ->where('month_cycle', $monthCycle)
            ->orderBy('member_id')
            ->get();

        $filename = 'attendance_monthly_'.$monthCycle.'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['member_code', 'member_name', 'month_cycle', 'present_days', 'is_locked', 'approved_at']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->member?->member_code,
                    $row->member?->name,
                    $row->month_cycle,
                    $row->present_days,
                    $row->is_locked ? '1' : '0',
                    $row->approved_at,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
