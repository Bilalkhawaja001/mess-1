<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\KitchenIssue;
use App\Models\Mess;
use App\Models\MealPlan;
use App\Models\Menu;
use App\Models\Recipe;
use App\Models\StockTransaction;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KitchenController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function index(Request $request)
    {
        $items = Item::query()->with('units')->orderBy('name')->get();
        $issueItems = $items
            ->where('is_active', true)
            ->map(function (Item $item) {
                $availableQty = $this->inventoryService->balanceForItem($item->id);
                $item->setAttribute('available_qty', $availableQty);

                return $item;
            })
            ->filter(fn (Item $item) => (float) ($item->available_qty ?? 0) > 0)
            ->values();
        $menus = Menu::query()->latest()->get();
        $recipes = Recipe::query()->latest()->limit(200)->get();
        $plans = MealPlan::query()->latest('plan_date')->limit(200)->get();
        $issues = KitchenIssue::query()->with(['mess', 'approvedStockTransaction'])->latest('issue_date')->limit(200)->get();
        $messes = Mess::query()->where('is_active', true)->orderBy('name')->get();

        $selectedMonth = (string) ($request->query('month') ?: now()->format('Y-m'));
        $monthStart = now()->startOfMonth();
        try {
            $monthStart = \Illuminate\Support\Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable $e) {
            $selectedMonth = now()->format('Y-m');
            $monthStart = now()->startOfMonth();
        }
        $monthEnd = $monthStart->copy()->endOfMonth();

        $approvedIssueQuery = KitchenIssue::query()
            ->with(['mess', 'approvedStockTransaction', 'item'])
            ->where('status', KitchenIssue::STATUS_APPROVED)
            ->whereNotNull('approved_stock_txn_id')
            ->whereBetween('issue_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        $approvedIssuesMonth = $approvedIssueQuery
            ->clone()
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->get();

        $kitchenMonthSummary = [
            'selected_month' => $selectedMonth,
            'month_start' => $monthStart,
            'month_end' => $monthEnd,
            'approved_issue_count' => $approvedIssuesMonth->count(),
            'approved_total_qty' => round((float) $approvedIssuesMonth->sum('quantity'), 3),
            'draft_issue_count' => KitchenIssue::query()
                ->where('status', KitchenIssue::STATUS_DRAFT)
                ->whereBetween('issue_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->count(),
        ];

        $consumption = $approvedIssuesMonth
            ->groupBy('item_id')
            ->map(function ($rows, $itemId) use ($items) {
                return (object) [
                    'item_id' => (int) $itemId,
                    'total_qty' => round((float) collect($rows)->sum('quantity'), 3),
                    'item' => $items->firstWhere('id', (int) $itemId),
                ];
            })
            ->sortByDesc('total_qty')
            ->values();

        $kitchenMonthByMess = $approvedIssuesMonth
            ->groupBy('mess_id')
            ->map(function ($rows, $messId) use ($messes) {
                return [
                    'mess_id' => (int) $messId,
                    'mess_name' => $messes->firstWhere('id', (int) $messId)?->name ?? '—',
                    'issue_count' => collect($rows)->count(),
                    'total_qty' => round((float) collect($rows)->sum('quantity'), 3),
                ];
            })
            ->sortByDesc('total_qty')
            ->values();

        $kitchenMonthByType = $approvedIssuesMonth
            ->groupBy(fn (KitchenIssue $issue) => $issue->issue_type ?: 'CONSUMPTION')
            ->map(function ($rows, $issueType) {
                return [
                    'issue_type' => $issueType,
                    'issue_count' => collect($rows)->count(),
                    'total_qty' => round((float) collect($rows)->sum('quantity'), 3),
                ];
            })
            ->sortByDesc('total_qty')
            ->values();

        $kitchenMonthDaily = $approvedIssuesMonth
            ->groupBy(fn (KitchenIssue $issue) => (string) $issue->issue_date)
            ->map(function ($rows, $issueDate) {
                return [
                    'issue_date' => $issueDate,
                    'issue_count' => collect($rows)->count(),
                    'total_qty' => round((float) collect($rows)->sum('quantity'), 3),
                ];
            })
            ->sortBy('issue_date')
            ->values();

        $kitchenMonthLedger = $approvedIssuesMonth
            ->map(function (KitchenIssue $issue) {
                return [
                    'issue_date' => $issue->issue_date,
                    'approved_at' => $issue->approved_at,
                    'mess_name' => $issue->mess?->name ?? '—',
                    'item_name' => $issue->item?->name ?? $issue->item_id,
                    'item_uom' => $issue->item?->uom,
                    'quantity' => round((float) $issue->quantity, 3),
                    'issue_type' => $issue->issue_type ?: 'CONSUMPTION',
                    'remarks' => $issue->remarks,
                    'stock_txn_id' => $issue->approved_stock_txn_id,
                ];
            })
            ->values();

        return view('admin.kitchen.index', compact('items', 'issueItems', 'menus', 'recipes', 'plans', 'issues', 'consumption', 'messes', 'selectedMonth', 'kitchenMonthSummary', 'kitchenMonthByMess', 'kitchenMonthByType', 'kitchenMonthDaily', 'kitchenMonthLedger'));
    }

    public function apiMenus(): JsonResponse
    {
        $rows = Menu::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'meal_type']);

        return response()->json(['menus' => $rows]);
    }

    public function storeMenu(Request $request): RedirectResponse
    {
        Menu::query()->create($request->validate([
            'name' => 'required|string|max:255',
            'meal_type' => 'required|string|max:30',
        ]));

        return back()->with('success', 'Menu created');
    }

    public function updateMenu(Request $request, Menu $menu): RedirectResponse
    {
        $menu->update($request->validate([
            'name' => 'required|string|max:255',
            'meal_type' => 'required|string|max:30',
            'is_active' => 'nullable|boolean',
        ]));

        return back()->with('success', 'Menu updated');
    }

    public function deleteMenu(Menu $menu): RedirectResponse
    {
        $menu->delete();

        return back()->with('success', 'Menu deleted');
    }

    public function storeRecipe(Request $request): RedirectResponse
    {
        Recipe::query()->create($request->validate([
            'menu_id' => 'required|exists:menus,id',
            'item_id' => 'required|exists:items,id',
            'qty_per_serving' => 'required|numeric|min:0.0001',
        ]));

        return back()->with('success', 'Recipe added');
    }

    public function updateRecipe(Request $request, Recipe $recipe): RedirectResponse
    {
        $recipe->update($request->validate([
            'menu_id' => 'required|exists:menus,id',
            'item_id' => 'required|exists:items,id',
            'qty_per_serving' => 'required|numeric|min:0.0001',
        ]));

        return back()->with('success', 'Recipe line updated');
    }

    public function deleteRecipe(Recipe $recipe): RedirectResponse
    {
        $recipe->delete();

        return back()->with('success', 'Recipe line deleted');
    }

    public function storePlan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan_date' => 'required|date',
            'menu_id' => 'required|exists:menus,id',
            'planned_servings' => 'required|integer|min:1',
        ]);

        MealPlan::query()->create($data + [
            'status' => MealPlan::STATUS_DRAFT,
            'approved_at' => null,
        ]);

        return back()->with('success', 'Meal plan created');
    }

    public function updatePlan(Request $request, MealPlan $plan): RedirectResponse
    {
        $plan->update($request->validate([
            'plan_date' => 'required|date',
            'menu_id' => 'required|exists:menus,id',
            'planned_servings' => 'required|integer|min:1',
        ]));

        return back()->with('success', 'Meal plan updated');
    }

    public function approvePlan(MealPlan $plan): RedirectResponse
    {
        if ($plan->isApproved()) {
            return back()->with('success', 'Meal plan already approved');
        }

        $plan->update([
            'status' => MealPlan::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Meal plan approved');
    }

    public function issue(Request $request): RedirectResponse
    {
        $d = $request->validate([
            'issue_date' => 'required|date',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|numeric|min:0.001',
            'unit_code' => 'nullable|string|max:20',
            'issue_type' => 'required|string|in:CONSUMPTION,WASTAGE,DAMAGE,EXPIRED',
            'mess_id' => 'required|exists:messes,id',
        ]);

        $item = Item::query()->with('units')->findOrFail($d['item_id']);
        $unitCode = $d['unit_code'] ?? null;
        $transQuantity = (float) $d['quantity'];

        $baseQuantity = $transQuantity;
        $transUnitCode = null;
        $transQty = null;

        if ($unitCode !== null && $unitCode !== '') {
            $unit = $item->units->firstWhere('unit_code', $unitCode);
            if (! $unit) {
                return back()
                    ->withErrors(['unit_code' => 'Invalid unit for item'])
                    ->withInput();
            }

            $baseQuantity = $transQuantity * (float) $unit->factor_to_base;
            $transUnitCode = $unit->unit_code;
            $transQty = $transQuantity;
        }

        // Lightweight duplicate guard: block very recent identical issues from being posted twice.
        $recentDuplicate = KitchenIssue::query()
            ->where('item_id', $item->id)
            ->where('issue_date', $d['issue_date'])
            ->where('quantity', $baseQuantity)
            ->where('issue_type', $d['issue_type'])
            ->where('mess_id', $d['mess_id'])
            ->where('remarks', $request->input('remarks'))
            ->where('created_at', '>=', now()->subMinutes(2))
            ->exists();

        if ($recentDuplicate) {
            return back()->with('info', 'Similar kitchen issue was just created. Duplicate posting has been skipped.');
        }

        $currentBalance = $this->inventoryService->balanceForItem($item->id);
        if ($baseQuantity > $currentBalance) {
            return back()->withErrors(['quantity' => 'Not enough stock to post this kitchen issue. Current balance: '.number_format($currentBalance, 3).' '.$item->uom])->withInput();
        }

        KitchenIssue::query()->create([
            'issue_date' => $d['issue_date'],
            'item_id' => $item->id,
            'quantity' => $baseQuantity,
            'mess_id' => $d['mess_id'],
            'issue_type' => $d['issue_type'],
            'remarks' => $request->input('remarks'),
            'status' => KitchenIssue::STATUS_DRAFT,
            'approved_at' => null,
            'approved_stock_txn_id' => null,
        ]);

        return back()->with('success', 'Kitchen issue created');
    }

    public function approveIssue(KitchenIssue $issue): RedirectResponse
    {
        if ($issue->isApproved()) {
            return back()->with('success', 'Kitchen issue already approved');
        }

        DB::transaction(function () use ($issue): void {
            $issue->refresh();

            if ($issue->isApproved()) {
                return;
            }

            $item = Item::query()->findOrFail($issue->item_id);
            $currentBalance = $this->inventoryService->balanceForItem($item->id);
            if ((float) $issue->quantity > $currentBalance) {
                throw new \RuntimeException('Not enough stock to approve this kitchen issue. Current balance: '.number_format($currentBalance, 3).' '.$item->uom);
            }

            $txn = StockTransaction::query()->create([
                'item_id' => $item->id,
                'txn_type' => StockTransaction::TXN_TYPE_KITCHEN_ISSUE,
                'quantity' => $issue->quantity,
                'unit_cost' => 0,
                'trans_unit_code' => null,
                'trans_quantity' => null,
                'reference_type' => KitchenIssue::class,
                'reference_id' => $issue->id,
                'txn_at' => $issue->issue_date,
                'remarks' => $issue->remarks ?: sprintf(
                    'Kitchen issue (%s%s)',
                    $issue->issue_type ?? 'CONSUMPTION',
                    $issue->mess_id ? ', Mess: '.$issue->mess?->name : ''
                ),
            ]);

            $issue->update([
                'status' => KitchenIssue::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_stock_txn_id' => $txn->id,
            ]);
        });

        return back()->with('success', 'Kitchen issue approved');
    }
}
