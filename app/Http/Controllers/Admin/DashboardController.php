<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\BillingCycle;
use App\Models\GuestMeal;
use App\Models\Member;
use App\Models\MemberLedger;
use App\Models\Payment;
use App\Models\User;
use App\Support\BusinessMonthCycle;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $totalBillable = (float) Billing::query()->sum('net_payable');
        $totalCollected = (float) MemberLedger::query()->sum('credit');
        $outstanding = round($totalBillable - $totalCollected, 2);

        $dashboardMonthCycle = trim((string) $request->query('dashboard_month_cycle', BusinessMonthCycle::defaultDashboardMonthCycle()));
        $dashboardCategoryCards = $this->buildDashboardCategoryCards($dashboardMonthCycle);

        $recentCycles = BillingCycle::query()->latest('month_cycle')->limit(5)->get()->map(function (BillingCycle $cycle) {
            return [
                'month_cycle' => $cycle->month_cycle,
                'status' => $cycle->status,
                'summary' => $cycle->is_closed ? 'Closed cycle' : 'Open cycle',
            ];
        })->all();

        $recentActivity = MemberLedger::query()->with('member')->latest('entry_date')->latest('id')->limit(10)->get()->map(function (MemberLedger $ledger) {
            return [
                'title' => trim(($ledger->member->member_code ?? 'Member') . ' ' . $ledger->ref_type . ' #' . $ledger->ref_id),
                'time' => optional($ledger->entry_date)->format('Y-m-d') ?? '',
            ];
        })->all();

        $stats = [
            'users' => User::query()->count(),
            'members' => Member::query()->count(),
            'open_cycles' => BillingCycle::query()->where('is_closed', false)->count(),
            'pending_payments' => Payment::query()->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_INITIATED, Payment::STATUS_RECONCILIATION_PENDING])->count(),
            'collections' => $totalCollected,
            'collected' => $totalCollected,
            'billable' => $totalBillable,
            'outstanding' => $outstanding,
            'recent_cycles' => $recentCycles,
            'recentCycles' => $recentCycles,
            'recent_activity' => $recentActivity,
            'recentActivity' => $recentActivity,
            'dashboard_month_cycle' => $dashboardMonthCycle,
            'dashboard_category_cards' => $dashboardCategoryCards,
        ];

        return view('admin.dashboard', compact('stats'));
    }

    private function buildDashboardCategoryCards(string $monthCycle): array
    {
        $cycle = BusinessMonthCycle::resolve($monthCycle);
        $rangeStart = $cycle['cycle_start'];
        $rangeEnd = $cycle['cycle_end'];

        $messTotals = Billing::query()
            ->selectRaw('UPPER(COALESCE(messes.code, messes.name, "")) as mess_code, COALESCE(SUM(billings.net_payable), 0) as total_expenses')
            ->join('members', 'members.id', '=', 'billings.member_id')
            ->leftJoin('messes', 'messes.id', '=', 'members.mess_id')
            ->where('billings.month_cycle', $monthCycle)
            ->groupByRaw('UPPER(COALESCE(messes.code, messes.name, ""))')
            ->pluck('total_expenses', 'mess_code');

        $guestTotal = (float) GuestMeal::query()
            ->whereNotNull('approved_at')
            ->whereDate('meal_date', '>=', $rangeStart->toDateString())
            ->whereDate('meal_date', '<=', $rangeEnd->toDateString())
            ->sum('amount');

        return [
            [
                'label' => 'Contractors',
                'mess_code' => 'CONTRACTORS',
                'month_cycle' => $monthCycle,
                'range_label' => $rangeStart->format('d M') . ' to ' . $rangeEnd->format('d M'),
                'total_expenses' => round((float) ($messTotals['CONTRACTORS'] ?? $messTotals['CONTRACTOR'] ?? 0), 2),
                'theme' => 'contractors',
            ],
            [
                'label' => 'Executive',
                'mess_code' => 'EXECUTIVE',
                'month_cycle' => $monthCycle,
                'range_label' => $rangeStart->format('d M') . ' to ' . $rangeEnd->format('d M'),
                'total_expenses' => round((float) ($messTotals['EXECUTIVE'] ?? $messTotals['EXEC'] ?? 0), 2),
                'theme' => 'executive',
            ],
            [
                'label' => 'Centralized',
                'mess_code' => 'CENTRALIZED',
                'month_cycle' => $monthCycle,
                'range_label' => $rangeStart->format('d M') . ' to ' . $rangeEnd->format('d M'),
                'total_expenses' => round((float) ($messTotals['CENTRALIZED'] ?? $messTotals['CENTRALIZE'] ?? $messTotals['CENTRAL'] ?? 0), 2),
                'theme' => 'centralized',
            ],
            [
                'label' => 'Guest',
                'mess_code' => 'GUEST',
                'month_cycle' => $monthCycle,
                'range_label' => $rangeStart->format('d M') . ' to ' . $rangeEnd->format('d M'),
                'total_expenses' => round($guestTotal, 2),
                'theme' => 'guest',
            ],
        ];
    }

}
