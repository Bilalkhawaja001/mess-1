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
use Illuminate\Support\Str;

class KitchenController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function index(Request $request)
    {
        $activeTab = trim((string) $request->query('tab', 'issue'));
        if (! in_array($activeTab, ['issue', 'issues', 'ledger', 'menu', 'plans'], true)) {
            $activeTab = 'issue';
        }
        if ($activeTab === 'issues') {
            $activeTab = 'ledger';
        }

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
        $issues = KitchenIssue::query()->with(['mess', 'approvedStockTransaction', 'item'])->latest('issue_date')->limit(200)->get();
        $messes = Mess::query()->where('is_active', true)->orderBy('name')->get();

        $consumption = KitchenIssue::query()
            ->where('status', KitchenIssue::STATUS_APPROVED)
            ->selectRaw('item_id, SUM(quantity) as total_qty')
            ->groupBy('item_id')
            ->orderByDesc('total_qty')
            ->get();

        $selectedMonth = (string) ($request->query('month') ?: now()->format('Y-m'));
        try {
            $monthStart = \Illuminate\Support\Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable $e) {
            $selectedMonth = now()->format('Y-m');
            $monthStart = now()->startOfMonth();
        }
        $monthEnd = $monthStart->copy()->endOfMonth();

        $kitchenLedgerBase = StockTransaction::query()
            ->from('stock_transactions as st')
            ->join('items as i', 'i.id', '=', 'st.item_id')
            ->join('kitchen_issues as ki', function ($join) {
                $join->on('ki.id', '=', 'st.reference_id')
                    ->where('st.reference_type', '=', KitchenIssue::class);
            })
            ->leftJoin('messes as m', 'm.id', '=', 'ki.mess_id')
            ->where('st.txn_type', StockTransaction::TXN_TYPE_KITCHEN_ISSUE)
            ->where('st.reference_type', KitchenIssue::class)
            ->where('ki.status', KitchenIssue::STATUS_APPROVED)
            ->whereNotNull('ki.approved_stock_txn_id');

        $kitchenLedgerRows = (clone $kitchenLedgerBase)
            ->whereBetween('st.txn_at', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderByDesc('st.txn_at')
            ->orderByDesc('st.id')
            ->limit(200)
            ->get([
                'st.id',
                'st.txn_at',
                'st.txn_type',
                'st.quantity',
                'st.unit_cost',
                'st.reference_type',
                'st.reference_id',
                'st.remarks',
                'i.name as item_name',
                'i.uom as item_uom',
                'ki.issue_date as source_issue_date',
                'ki.approved_at as issue_approved_at',
                'ki.issue_type',
                'ki.mess_id',
                'm.name as mess_name',
                DB::raw('(st.quantity * st.unit_cost) as amount'),
            ]);

        $kitchenMonthlySummary = (clone $kitchenLedgerBase)
            ->groupBy(DB::raw("DATE_FORMAT(st.txn_at, '%Y-%m')"))
            ->orderByDesc(DB::raw("DATE_FORMAT(st.txn_at, '%Y-%m')"))
            ->limit(12)
            ->get([
                DB::raw("DATE_FORMAT(st.txn_at, '%Y-%m') as month_cycle"),
                DB::raw('COUNT(*) as ledger_rows'),
                DB::raw('SUM(st.quantity) as total_qty'),
                DB::raw('SUM(st.quantity * st.unit_cost) as total_amount'),
            ]);

        return view('admin.kitchen.index', compact(
            'items',
            'issueItems',
            'menus',
            'recipes',
            'plans',
            'issues',
            'consumption',
            'messes',
            'selectedMonth',
            'kitchenMonthlySummary',
            'kitchenLedgerRows',
            'activeTab'
        ));
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
        $transQty = null;
        if ($unitCode !== null && $unitCode !== '') {
            $unit = $item->units->firstWhere('unit_code', $unitCode);
            if (! $unit) {
                return back()->withErrors(['unit_code' => 'Invalid unit for item'])->withInput();
            }

            $baseQuantity = $transQuantity * (float) $unit->factor_to_base;
            $transQty = $transQuantity;
        }

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
            return back()->with('info', 'Similar kitchen issue was just created. Duplicate request has been skipped.');
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

        return back()->with('success', 'Kitchen issue request created and pending approval.');
    }

    public function approveIssue(Request $request, KitchenIssue $issue): RedirectResponse
    {
        $returnTab = $this->normalizeKitchenTab((string) $request->input('return_tab', $request->query('tab', 'ledger')));

        if ($issue->isApproved()) {
            return $this->redirectToKitchenTab($returnTab)->with('success', 'Kitchen issue already approved.');
        }

        $existingPosting = StockTransaction::query()
            ->where('txn_type', StockTransaction::TXN_TYPE_KITCHEN_ISSUE)
            ->where('reference_type', KitchenIssue::class)
            ->where('reference_id', $issue->id)
            ->first();

        if ($existingPosting) {
            $issue->forceFill([
                'status' => KitchenIssue::STATUS_APPROVED,
                'approved_at' => $issue->approved_at ?? now(),
                'approved_stock_txn_id' => $issue->approved_stock_txn_id ?? $existingPosting->id,
            ])->save();

            return $this->redirectToKitchenTab($returnTab)->with('success', 'Kitchen issue already had a stock posting. Approval state synced without duplicate posting.');
        }

        try {
            DB::transaction(function () use ($issue): void {
                $lockedIssue = KitchenIssue::query()
                    ->whereKey($issue->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedIssue->isApproved()) {
                    return;
                }

                $duplicatePosting = StockTransaction::query()
                    ->where('txn_type', StockTransaction::TXN_TYPE_KITCHEN_ISSUE)
                    ->where('reference_type', KitchenIssue::class)
                    ->where('reference_id', $lockedIssue->id)
                    ->lockForUpdate()
                    ->first();

                if ($duplicatePosting) {
                    $lockedIssue->forceFill([
                        'status' => KitchenIssue::STATUS_APPROVED,
                        'approved_at' => $lockedIssue->approved_at ?? now(),
                        'approved_stock_txn_id' => $lockedIssue->approved_stock_txn_id ?? $duplicatePosting->id,
                    ])->save();
                    return;
                }

                $item = Item::query()->findOrFail($lockedIssue->item_id);
                $currentBalance = $this->inventoryService->balanceForItem($item->id);
                if ((float) $lockedIssue->quantity > $currentBalance) {
                    throw new \RuntimeException('Not enough stock to approve this kitchen issue. Current balance: '.number_format($currentBalance, 3).' '.$item->uom);
                }

                $unitCost = $this->inventoryService->currentUnitCostForItem((int) $lockedIssue->item_id);

                $txn = StockTransaction::query()->create([
                    'item_id' => $item->id,
                    'txn_type' => StockTransaction::TXN_TYPE_KITCHEN_ISSUE,
                    'quantity' => $lockedIssue->quantity,
                    'unit_cost' => $unitCost,
                    'trans_unit_code' => null,
                    'trans_quantity' => null,
                    'reference_type' => KitchenIssue::class,
                    'reference_id' => $lockedIssue->id,
                    'txn_at' => $lockedIssue->issue_date,
                    'remarks' => $lockedIssue->remarks ?: sprintf(
                        'Kitchen issue (%s%s)',
                        $lockedIssue->issue_type ?? 'CONSUMPTION',
                        $lockedIssue->mess_id ? ', Mess: '.($lockedIssue->mess?->name ?? $lockedIssue->mess_id) : ''
                    ),
                ]);

                $lockedIssue->forceFill([
                    'status' => KitchenIssue::STATUS_APPROVED,
                    'approved_at' => now(),
                    'approved_stock_txn_id' => $txn->id,
                ])->save();
            });
        } catch (\RuntimeException $e) {
            return $this->redirectToKitchenTab($returnTab)->withErrors(['kitchen_issue' => $e->getMessage()]);
        }

        return $this->redirectToKitchenTab($returnTab)->with('success', 'Kitchen issue approved and stock posted successfully.');
    }

    private function normalizeKitchenTab(string $tab): string
    {
        $tab = Str::lower(trim($tab));

        return match ($tab) {
            'issues', 'ledger' => 'ledger',
            'menu' => 'menu',
            'plans' => 'plans',
            default => 'issue',
        };
    }

    private function redirectToKitchenTab(string $tab): RedirectResponse
    {
        return redirect()->route('admin.kitchen.index', ['tab' => $this->normalizeKitchenTab($tab)]);
    }
}
