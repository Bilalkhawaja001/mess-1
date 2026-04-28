<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Mess;
use App\Models\MessCosting;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class MessCostingController extends Controller
{
    public function index(Request $request): View
    {
        $monthCycle = (string) ($request->query('month_cycle') ?: now()->format('Y-m'));
        $messId = $request->query('mess_id');

        $messes = Mess::query()->orderBy('name')->get();
        $history = MessCosting::query()->with(['mess', 'creator'])->orderByDesc('id')->limit(25)->get();
        $preview = $this->buildSnapshot($monthCycle, $messId ? (int) $messId : null);

        return view('admin.mess-costing.index', compact('monthCycle', 'messId', 'messes', 'history', 'preview'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'month_cycle' => ['required', 'date_format:Y-m'],
            'mess_id' => ['nullable', 'exists:messes,id'],
            'food_cost' => ['required', 'numeric', 'min:0'],
            'gas_cost' => ['nullable', 'numeric', 'min:0'],
            'include_gas_cost' => ['nullable', 'boolean'],
            'salary_cost' => ['nullable', 'numeric', 'min:0'],
            'include_salary_cost' => ['nullable', 'boolean'],
            'other_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $snapshot = $this->buildSnapshot($data['month_cycle'], isset($data['mess_id']) ? (int) $data['mess_id'] : null);
        $foodCost = (float) $data['food_cost'];
        $gasCost = !empty($data['include_gas_cost']) ? (float) ($data['gas_cost'] ?? 0) : 0;
        $salaryCost = !empty($data['include_salary_cost']) ? (float) ($data['salary_cost'] ?? 0) : 0;
        $otherCost = (float) ($data['other_cost'] ?? 0);
        $totalCost = round($foodCost + $gasCost + $salaryCost + $otherCost, 2);
        $memberCount = (int) ($snapshot['member_count'] ?? 0);
        $activeDaysTotal = (float) ($snapshot['active_days_total'] ?? 0);
        $costPerMember = $memberCount > 0 ? round($totalCost / $memberCount, 2) : 0;
        $costPerDay = $activeDaysTotal > 0 ? round($totalCost / $activeDaysTotal, 4) : 0;

        $costing = MessCosting::query()->create([
            'month_cycle' => $data['month_cycle'],
            'mess_id' => $data['mess_id'] ?? null,
            'food_cost' => $foodCost,
            'gas_cost' => (float) ($data['gas_cost'] ?? 0),
            'include_gas_cost' => !empty($data['include_gas_cost']),
            'salary_cost' => (float) ($data['salary_cost'] ?? 0),
            'include_salary_cost' => !empty($data['include_salary_cost']),
            'other_cost' => $otherCost,
            'total_cost' => $totalCost,
            'member_count' => $memberCount,
            'active_days_total' => $activeDaysTotal,
            'cost_per_member' => $costPerMember,
            'cost_per_day' => $costPerDay,
            'comparison_json' => $snapshot['comparison'] ?? null,
            'snapshot_json' => $snapshot,
            'created_by' => optional(auth()->user())->id,
        ]);

        return redirect()->route('admin.mess-costing.show', $costing)->with('success', 'Mess costing snapshot saved.');
    }

    public function show(MessCosting $costing): View
    {
        $costing->load(['mess', 'creator']);

        return view('admin.mess-costing.show', compact('costing'));
    }

    public function print(MessCosting $costing): View
    {
        $costing->load(['mess', 'creator']);

        return view('admin.mess-costing.print', compact('costing'));
    }

    public function export(MessCosting $costing)
    {
        $costing->load(['mess', 'creator']);
        $snapshot = $costing->snapshot_json ?? [];
        $rows = [
            ['Month', $costing->month_cycle],
            ['Mess', optional($costing->mess)->name ?? 'All'],
            ['Food Cost', $costing->food_cost],
            ['Gas Cost Included', $costing->include_gas_cost ? 'Yes' : 'No'],
            ['Gas Cost', $costing->gas_cost],
            ['Salary Cost Included', $costing->include_salary_cost ? 'Yes' : 'No'],
            ['Salary Cost', $costing->salary_cost],
            ['Other Cost', $costing->other_cost],
            ['Total Cost', $costing->total_cost],
            ['Member Count', $costing->member_count],
            ['Active Days Total', $costing->active_days_total],
            ['Cost Per Member', $costing->cost_per_member],
            ['Cost Per Day', $costing->cost_per_day],
            [],
            ['Snapshot Basis', 'Value'],
            ['Attendance Rows', $snapshot['attendance_rows'] ?? 0],
            ['Detected Departments', implode(', ', $snapshot['department_names'] ?? [])],
        ];

        return Response::streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'mess_costing_'.$costing->id.'.csv', ['Content-Type' => 'text/csv']);
    }

    private function buildSnapshot(string $monthCycle, ?int $messId): array
    {
        $attendance = Attendance::query()
            ->whereYear('attendance_date', substr($monthCycle, 0, 4))
            ->whereMonth('attendance_date', substr($monthCycle, 5, 2))
            ->where('present', true)
            ->when($messId, function ($query) use ($messId) {
                $query->whereHas('member', fn ($member) => $member->where('mess_id', $messId));
            })
            ->with('member')
            ->get();

        $memberIds = $attendance->pluck('member_id')->filter()->unique()->values();
        $members = Member::query()
            ->when($messId, fn ($q) => $q->where('mess_id', $messId))
            ->whereIn('id', $memberIds)
            ->get();

        $departmentCounts = [];
        foreach ($members as $member) {
            $name = trim((string) ($member->department_name ?? ''));
            if ($name === '') {
                continue;
            }
            $departmentCounts[$name] = ($departmentCounts[$name] ?? 0) + 1;
        }

        $comparison = null;
        $supportedLabels = ['Admin', 'Spinning', 'Weaving'];
        $presentLabels = array_values(array_intersect($supportedLabels, array_keys($departmentCounts)));
        if (count($presentLabels) > 0) {
            $comparison = [];
            foreach ($presentLabels as $label) {
                $comparison[] = [
                    'name' => $label,
                    'member_count' => (int) ($departmentCounts[$label] ?? 0),
                ];
            }
        }

        return [
            'month_cycle' => $monthCycle,
            'mess_id' => $messId,
            'attendance_rows' => $attendance->count(),
            'member_count' => $memberIds->count(),
            'active_days_total' => round((float) $attendance->count(), 3),
            'department_names' => array_keys($departmentCounts),
            'comparison' => $comparison,
        ];
    }
}
