<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\BillingCycle;
use App\Models\GuestMeal;
use App\Models\Item;
use App\Models\Member;
use App\Models\Payment;
use App\Models\StockTransaction;
use App\Models\User;
use App\Support\BusinessMonthCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $availableCycles = $this->availableCycles();
        $dashboardMonthCycle = trim((string) $request->query('dashboard_month_cycle', $availableCycles[0] ?? BusinessMonthCycle::defaultDashboardMonthCycle()));
        if (! preg_match('/^\d{4}-\d{2}$/', $dashboardMonthCycle)) {
            $dashboardMonthCycle = $availableCycles[0] ?? BusinessMonthCycle::defaultDashboardMonthCycle();
        }

        $cycle = BusinessMonthCycle::resolve($dashboardMonthCycle);
        $previousMonthCycle = $this->previousCycle($dashboardMonthCycle, $availableCycles);

        $billable = $this->billableForCycle($dashboardMonthCycle);
        $collected = $this->collectedForCycle($dashboardMonthCycle);
        $outstanding = round($billable - $collected, 2);
        $recoveryRatio = $billable > 0 ? min(100, ($collected / $billable) * 100) : 0;

        $previousBillable = $previousMonthCycle ? $this->billableForCycle($previousMonthCycle) : 0.0;
        $previousCollected = $previousMonthCycle ? $this->collectedForCycle($previousMonthCycle) : 0.0;
        $previousRecoveryRatio = $previousBillable > 0 ? min(100, ($previousCollected / $previousBillable) * 100) : null;
        $recoveryDelta = $previousRecoveryRatio === null ? null : round($recoveryRatio - $previousRecoveryRatio, 1);

        $pendingStatuses = [Payment::STATUS_PENDING, Payment::STATUS_INITIATED, Payment::STATUS_RECONCILIATION_PENDING];
        $pendingPayments = Payment::query()
            ->join('billings', 'billings.id', '=', 'payments.bill_id')
            ->where('billings.month_cycle', $dashboardMonthCycle)
            ->whereIn('payments.status', $pendingStatuses)
            ->count();
        $pendingAmount = (float) Payment::query()
            ->join('billings', 'billings.id', '=', 'payments.bill_id')
            ->where('billings.month_cycle', $dashboardMonthCycle)
            ->whereIn('payments.status', $pendingStatuses)
            ->sum('payments.amount');

        $dashboardCategoryCards = $this->buildDashboardCategoryCards($dashboardMonthCycle);
        $categoryTotal = array_sum(array_map(fn ($row) => (float) ($row['total_expenses'] ?? 0), $dashboardCategoryCards));

        $trendRows = $this->buildExpenseTrendRows();
        $selectedCycleTotalExpense = $this->expenseTotalForCycle($dashboardMonthCycle);
        $previousCycleTotalExpense = $previousMonthCycle ? $this->expenseTotalForCycle($previousMonthCycle) : null;
        $expenseDeltaPercent = ($previousCycleTotalExpense !== null && $previousCycleTotalExpense > 0)
            ? round((($selectedCycleTotalExpense - $previousCycleTotalExpense) / $previousCycleTotalExpense) * 100, 1)
            : null;

        $cycleRecord = BillingCycle::query()->where('month_cycle', $dashboardMonthCycle)->first();
        $lowStock = $this->lowStockSnapshot();
        $health = $this->buildHealthSnapshot($recoveryRatio, $pendingAmount, $billable, $cycleRecord, $lowStock);
        $alerts = $this->buildAlerts($dashboardMonthCycle, $outstanding, $pendingPayments, $pendingAmount, $lowStock, $expenseDeltaPercent, $cycleRecord);

        $activeMembers = Member::query()->where('is_active', true)->count();
        $billableMembers = Billing::query()->where('month_cycle', $dashboardMonthCycle)->distinct('member_id')->count('member_id');
        $avgBillPerMember = $billableMembers > 0 ? $billable / $billableMembers : 0;

        $stats = [
            'users' => User::query()->count(),
            'active_users' => User::query()->where('is_active', true)->count(),
            'members' => Member::query()->count(),
            'active_members' => $activeMembers,
            'billable_members' => $billableMembers,
            'open_cycles' => BillingCycle::query()->where('is_closed', false)->count(),
            'pending_payments' => $pendingPayments,
            'pending_amount' => $pendingAmount,
            'collections' => $collected,
            'collected' => $collected,
            'billable' => $billable,
            'outstanding' => $outstanding,
            'recovery_ratio' => $recoveryRatio,
            'previous_month_cycle' => $previousMonthCycle,
            'previous_recovery_ratio' => $previousRecoveryRatio,
            'recovery_delta' => $recoveryDelta,
            'available_cycles' => $availableCycles,
            'dashboard_month_cycle' => $dashboardMonthCycle,
            'cycle_range_label' => $cycle['cycle_start']->format('d M Y') . ' to ' . $cycle['cycle_end']->format('d M Y'),
            'cycle_record' => $cycleRecord,
            'dashboard_category_cards' => $dashboardCategoryCards,
            'category_total' => $categoryTotal,
            'expense_trend_rows' => $trendRows,
            'expense_trend_available' => count($trendRows) >= 2,
            'selected_cycle_total_expense' => $selectedCycleTotalExpense,
            'previous_cycle_total_expense' => $previousCycleTotalExpense,
            'expense_delta_percent' => $expenseDeltaPercent,
            'low_stock' => $lowStock,
            'health' => $health,
            'alerts' => $alerts,
            'avg_bill_per_member' => $avgBillPerMember,
            'page_generated_at' => now()->format('d-M-Y h:i A'),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    private function availableCycles(): array
    {
        $cycles = BillingCycle::query()->orderByDesc('month_cycle')->pluck('month_cycle')->filter()->values();
        $billingCycles = Billing::query()->select('month_cycle')->distinct()->orderByDesc('month_cycle')->pluck('month_cycle')->filter()->values();

        return $cycles->merge($billingCycles)->unique()->values()->all();
    }

    private function previousCycle(string $monthCycle, array $availableCycles): ?string
    {
        return collect($availableCycles)->filter(fn ($cycle) => $cycle < $monthCycle)->sortDesc()->first();
    }

    private function billableForCycle(string $monthCycle): float
    {
        return (float) Billing::query()->where('month_cycle', $monthCycle)->sum('net_payable');
    }

    private function collectedForCycle(string $monthCycle): float
    {
        $paidStatuses = [Payment::STATUS_SUCCESS, Payment::STATUS_RECONCILED, Payment::STATUS_APPROVED];

        return (float) Payment::query()
            ->join('billings', 'billings.id', '=', 'payments.bill_id')
            ->where('billings.month_cycle', $monthCycle)
            ->whereIn('payments.status', $paidStatuses)
            ->sum(DB::raw('GREATEST(payments.amount - COALESCE(payments.refunded_amount, 0) - COALESCE(payments.reversed_amount, 0), 0)'));
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
            $this->categoryCard('Executive', 'EXECUTIVE', $monthCycle, $rangeStart, $rangeEnd, (float) ($messTotals['EXECUTIVE'] ?? $messTotals['EXEC'] ?? 0), 'executive'),
            $this->categoryCard('Centralized', 'CENTRALIZED', $monthCycle, $rangeStart, $rangeEnd, (float) ($messTotals['CENTRALIZED'] ?? $messTotals['CENTRALIZE'] ?? $messTotals['CENTRAL'] ?? 0), 'centralized'),
            $this->categoryCard('Contractors', 'CONTRACTORS', $monthCycle, $rangeStart, $rangeEnd, (float) ($messTotals['CONTRACTORS'] ?? $messTotals['CONTRACTOR'] ?? 0), 'contractors'),
            $this->categoryCard('Guests', 'GUEST', $monthCycle, $rangeStart, $rangeEnd, $guestTotal, 'guest'),
        ];
    }

    private function categoryCard(string $label, string $messCode, string $monthCycle, $rangeStart, $rangeEnd, float $total, string $theme): array
    {
        return [
            'label' => $label,
            'mess_code' => $messCode,
            'month_cycle' => $monthCycle,
            'range_label' => $rangeStart->format('d M') . ' to ' . $rangeEnd->format('d M'),
            'total_expenses' => round($total, 2),
            'theme' => $theme,
        ];
    }

    private function expenseTotalForCycle(string $monthCycle): float
    {
        $billingsTotal = $this->billableForCycle($monthCycle);
        $cycle = BusinessMonthCycle::resolve($monthCycle);
        $guestTotal = (float) GuestMeal::query()
            ->whereNotNull('approved_at')
            ->whereDate('meal_date', '>=', $cycle['cycle_start']->toDateString())
            ->whereDate('meal_date', '<=', $cycle['cycle_end']->toDateString())
            ->sum('amount');

        return round($billingsTotal + $guestTotal, 2);
    }

    private function buildExpenseTrendRows(): array
    {
        $closedCycles = BillingCycle::query()
            ->where('is_closed', true)
            ->orderByDesc('month_cycle')
            ->limit(6)
            ->pluck('month_cycle')
            ->filter()
            ->reverse()
            ->values();

        if ($closedCycles->count() < 2) {
            return [];
        }

        $rows = $closedCycles->map(fn ($cycle) => [
            'month_cycle' => $cycle,
            'total_expense' => $this->expenseTotalForCycle($cycle),
        ])->values()->all();

        $max = max(array_map(fn ($row) => (float) $row['total_expense'], $rows)) ?: 0;

        return array_map(function ($row) use ($max) {
            $row['bar_percent'] = $max > 0 ? round(((float) $row['total_expense'] / $max) * 100, 2) : 0;
            return $row;
        }, $rows);
    }

    private function lowStockSnapshot(): array
    {
        if (! Schema::hasTable('items') || ! Schema::hasTable('stock_transactions') || ! Schema::hasColumn('items', 'reorder_level')) {
            return ['available' => false, 'count' => null, 'items' => []];
        }

        $stockTotals = StockTransaction::query()
            ->select('item_id', DB::raw('COALESCE(SUM(quantity), 0) as current_stock'))
            ->groupBy('item_id');

        $items = Item::query()
            ->leftJoinSub($stockTotals, 'stock_totals', 'stock_totals.item_id', '=', 'items.id')
            ->where('items.is_active', true)
            ->whereNotNull('items.reorder_level')
            ->whereRaw('COALESCE(stock_totals.current_stock, 0) <= items.reorder_level')
            ->orderBy('items.name')
            ->limit(5)
            ->get(['items.name', 'items.reorder_level', DB::raw('COALESCE(stock_totals.current_stock, 0) as current_stock')])
            ->map(fn ($item) => [
                'name' => $item->name,
                'current_stock' => (float) $item->current_stock,
                'reorder_level' => (float) $item->reorder_level,
            ])->all();

        $count = Item::query()
            ->leftJoinSub($stockTotals, 'stock_totals', 'stock_totals.item_id', '=', 'items.id')
            ->where('items.is_active', true)
            ->whereNotNull('items.reorder_level')
            ->whereRaw('COALESCE(stock_totals.current_stock, 0) <= items.reorder_level')
            ->count();

        return ['available' => true, 'count' => $count, 'items' => $items];
    }

    private function buildHealthSnapshot(float $recoveryRatio, float $pendingAmount, float $billable, ?BillingCycle $cycleRecord, array $lowStock): array
    {
        if (! $cycleRecord || ! ($lowStock['available'] ?? false)) {
            return ['available' => false, 'score' => null, 'label' => 'Health score not available', 'components' => []];
        }

        $collectionScore = round(min(100, max(0, $recoveryRatio)), 1);
        $pendingPressurePercent = $billable > 0 ? min(100, ($pendingAmount / $billable) * 100) : 0;
        $pendingScore = round(max(0, 100 - $pendingPressurePercent), 1);
        $cycleScore = $cycleRecord->is_closed ? 100 : (strtoupper((string) $cycleRecord->status) === 'OPEN' ? 70 : 80);
        $inventoryScore = (int) ($lowStock['count'] ?? 0) === 0 ? 100 : max(0, 100 - ((int) $lowStock['count'] * 20));

        $components = [
            ['label' => 'Collection efficiency', 'score' => $collectionScore, 'weight' => 40, 'source' => 'payments for selected cycle / billings for selected cycle'],
            ['label' => 'Pending payment pressure', 'score' => $pendingScore, 'weight' => 20, 'source' => 'pending payments amount / billings for selected cycle'],
            ['label' => 'Billing cycle status', 'score' => $cycleScore, 'weight' => 20, 'source' => 'billing_cycles.is_closed/status'],
            ['label' => 'Inventory risk', 'score' => $inventoryScore, 'weight' => 20, 'source' => 'items.reorder_level vs stock_transactions quantity sum'],
        ];

        $weighted = array_sum(array_map(fn ($row) => ($row['score'] * $row['weight']) / 100, $components));
        $score = round($weighted, 1);

        return [
            'available' => true,
            'score' => $score,
            'label' => $score >= 85 ? 'Healthy' : ($score >= 70 ? 'Watch' : 'Needs attention'),
            'components' => $components,
        ];
    }

    private function buildAlerts(string $monthCycle, float $outstanding, int $pendingPayments, float $pendingAmount, array $lowStock, ?float $expenseDeltaPercent, ?BillingCycle $cycleRecord): array
    {
        $alerts = [];

        if ($outstanding > 0) {
            $alerts[] = ['type' => 'Outstanding', 'message' => 'Outstanding balance exists for selected cycle.', 'detail' => 'PKR ' . number_format($outstanding, 2)];
        }

        if ($pendingPayments > 0) {
            $alerts[] = ['type' => 'Pending payments', 'message' => $pendingPayments . ' payment(s) pending for selected cycle.', 'detail' => 'Pending amount: PKR ' . number_format($pendingAmount, 2)];
        }

        if (($lowStock['available'] ?? false) && (int) ($lowStock['count'] ?? 0) > 0) {
            $first = $lowStock['items'][0] ?? null;
            $detail = $first ? ($first['name'] . ': ' . number_format($first['current_stock'], 3) . ' / reorder ' . number_format($first['reorder_level'], 3)) : '';
            $alerts[] = ['type' => 'Low stock', 'message' => $lowStock['count'] . ' inventory item(s) are at or below reorder level.', 'detail' => $detail];
        }

        if ($expenseDeltaPercent !== null && $expenseDeltaPercent > 0) {
            $alerts[] = ['type' => 'Expense increase', 'message' => 'Selected cycle expense is higher than previous cycle.', 'detail' => number_format($expenseDeltaPercent, 1) . '% increase'];
        }

        if ($cycleRecord && ! $cycleRecord->is_closed) {
            $alerts[] = ['type' => 'Cycle status', 'message' => 'Selected billing cycle is not closed.', 'detail' => 'Status: ' . ($cycleRecord->status ?: 'open')];
        }

        return $alerts;
    }
}
