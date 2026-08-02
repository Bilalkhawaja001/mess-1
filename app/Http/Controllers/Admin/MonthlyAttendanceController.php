<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\MonthlyAttendanceRequest;
use App\Models\Attendance;
use App\Models\Member;
use App\Models\MonthlyAttendance;
use App\Support\BusinessMonthCycle;
use Carbon\Carbon;
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
        $cycle = BusinessMonthCycle::resolve($monthCycle);
        $start = $cycle['cycle_start_date'];
        $end = $cycle['cycle_end_date'];

        $members = Member::query()
            ->where(function ($q) use ($start) {
                $q->where('is_active', true)
                  ->orWhereDate('leave_date', '>=', $start);
            })
            ->where(function ($q) use ($end) {
                $q->whereNull('join_date')->orWhereDate('join_date', '<=', $end);
            })
            ->orderBy('member_code')->get();
        $snap = MonthlyAttendance::query()->where('month_cycle', $monthCycle)->get()->keyBy('member_id');
        $monthCards = $this->buildMonthCards($monthCycle);

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

        return view('admin.attendance_monthly.index', compact('monthCycle', 'rows', 'monthCards'));
    }

    public function store(MonthlyAttendanceRequest $request): RedirectResponse
    {
        $monthCycle = $request->input('month_cycle');
        $cycle = BusinessMonthCycle::resolve((string) $monthCycle);

        foreach ($request->input('rows', []) as $row) {
            $presentDays = (int) $row['present_days'];
            $validationError = $this->validateCyclePresentDays((string) $monthCycle, $presentDays, $cycle['cycle_days']);
            if ($validationError !== null) {
                return redirect()->route('admin.attendance-monthly.index', ['month_cycle' => $monthCycle])->with('error', $validationError)->withInput();
            }
        }

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

    public function manualStore(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'month_cycle' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'present_days' => ['required', 'integer', 'min:0', 'max:31'],
        ]);

        $monthCycle = (string) $payload['month_cycle'];
        $memberId = (int) $payload['member_id'];
        $presentDays = (int) $payload['present_days'];

        $cycle = BusinessMonthCycle::resolve($monthCycle);
        if ($presentDays > $cycle['cycle_days']) {
            return back()->with('error', "present_days cannot exceed {$cycle['cycle_days']} for business cycle {$monthCycle}.")->withInput();
        }

        $existing = MonthlyAttendance::query()
            ->where('month_cycle', $monthCycle)
            ->where('member_id', $memberId)
            ->first();

        if ($existing && $existing->is_locked) {
            return back()->with('error', 'Monthly attendance is locked. Unlock before editing.')->withInput();
        }

        MonthlyAttendance::query()->updateOrCreate(
            ['month_cycle' => $monthCycle, 'member_id' => $memberId],
            [
                'present_days' => $presentDays,
                'approved_by_user_id' => $existing?->approved_by_user_id,
                'approved_at' => $existing?->approved_at,
                'is_locked' => $existing?->is_locked ?? false,
            ]
        );

        return redirect()->route('admin.attendance-monthly.index', ['month_cycle' => $monthCycle])
            ->with('success', 'Manual monthly attendance saved.');
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

            $cycle = BusinessMonthCycle::resolve($monthCycle);
            if ($presentDays > $cycle['cycle_days']) {
                $errors[] = "Line {$line}: present_days cannot exceed {$cycle['cycle_days']} for business cycle {$monthCycle}.";
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

    private function buildMonthCards(string $baseMonthCycle): array
    {
        $baseMonth = Carbon::createFromFormat('Y-m', $baseMonthCycle)->startOfMonth();
        $cardMonths = collect(range(0, 3))
            ->map(fn (int $offset) => $baseMonth->copy()->subMonths($offset)->format('Y-m'))
            ->all();

        $cards = [];
        foreach ($cardMonths as $monthCycle) {
            $cycle = BusinessMonthCycle::resolve($monthCycle);
            $cards[$monthCycle] = [
                'month_cycle' => $monthCycle,
                'cycle_start' => $cycle['cycle_start_date'],
                'cycle_end' => $cycle['cycle_end_date'],
                'cycle_days' => $cycle['cycle_days'],
                'contractors' => 0,
                'executive' => 0,
                'centralized' => 0,
            ];
        }

        $rows = MonthlyAttendance::query()
            ->with('member.mess')
            ->whereIn('month_cycle', $cardMonths)
            ->get();

        foreach ($rows as $row) {
            $messBucket = $this->normalizeMonthCardMess((string) ($row->member?->mess?->code ?: $row->member?->mess?->name ?: ''));
            if ($messBucket === null || ! isset($cards[$row->month_cycle])) {
                continue;
            }

            $cards[$row->month_cycle][$messBucket] += (int) $row->present_days;
        }

        return array_values(array_map(fn (string $monthCycle) => $cards[$monthCycle], $cardMonths));
    }

    private function normalizeMonthCardMess(string $messCode): ?string
    {
        $messCode = strtoupper(trim($messCode));

        return match ($messCode) {
            'CONTRACTOR', 'CONTRACTORS' => 'contractors',
            'EXEC', 'EXECUTIVE' => 'executive',
            'CENTRAL', 'CENTRALIZE', 'CENTRALIZED' => 'centralized',
            default => null,
        };
    }

    private function validateCyclePresentDays(string $monthCycle, int $presentDays, int $cycleDays): ?string
    {
        if ($presentDays < 0) {
            return 'present_days must be >= 0.';
        }

        if ($presentDays > $cycleDays) {
            return "present_days cannot exceed {$cycleDays} for business cycle {$monthCycle}.";
        }

        return null;
    }
}
