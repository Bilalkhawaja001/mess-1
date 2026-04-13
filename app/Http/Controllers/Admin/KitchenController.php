<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\KitchenIssue;
use App\Models\KitchenIssueTarget;
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

    public function index()
    {
        $items = Item::query()->with('units')->orderBy('name')->get();
        $issueItems = $items
            ->where('is_active', true)
            ->filter(fn (Item $item) => $this->inventoryService->balanceForItem($item->id) > 0)
            ->values();
        $issueTargets = KitchenIssueTarget::query()
            ->select(['id', 'target_date', 'mess_id', 'item_id', 'required_qty', 'issued_qty', 'status'])
            ->get()
            ->map(function (KitchenIssueTarget $target) {
                $required = round((float) $target->required_qty, 3);
                $issued = round((float) $target->issued_qty, 3);
                $pending = round(max($required - $issued, 0), 3);

                return [
                    'id' => $target->id,
                    'target_date' => optional($target->target_date)->format('Y-m-d'),
                    'mess_id' => (int) $target->mess_id,
                    'item_id' => (int) $target->item_id,
                    'required_qty' => $required,
                    'issued_qty' => $issued,
                    'pending_qty' => $pending,
                    'status' => $pending > 0 ? KitchenIssueTarget::STATUS_OPEN : KitchenIssueTarget::STATUS_COMPLETED,
                ];
            })
            ->values();
        $menus = Menu::query()->latest()->get();
        $recipes = Recipe::query()->latest()->limit(200)->get();
        $plans = MealPlan::query()->latest('plan_date')->limit(200)->get();
        $issues = KitchenIssue::query()->latest('issue_date')->limit(200)->get();
        $consumption = KitchenIssue::query()->selectRaw('item_id, sum(quantity) total_qty')->groupBy('item_id')->get();
        $messes = Mess::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.kitchen.index', compact('items', 'issueItems', 'issueTargets', 'menus', 'recipes', 'plans', 'issues', 'consumption', 'messes'));
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

        try {
            DB::transaction(function () use ($d, $request, $item, $baseQuantity): void {
                $target = KitchenIssueTarget::query()
                    ->whereDate('target_date', $d['issue_date'])
                    ->where('mess_id', (int) $d['mess_id'])
                    ->where('item_id', $item->id)
                    ->lockForUpdate()
                    ->first();

                if (! $target) {
                    throw new \RuntimeException('No kitchen issue target exists for the selected date, mess, and item.');
                }

                $requiredQty = round((float) $target->required_qty, 3);
                $issuedQty = round((float) $target->issued_qty, 3);
                $pendingQty = round(max($requiredQty - $issuedQty, 0), 3);

                if ($pendingQty <= 0) {
                    throw new \RuntimeException('This item is already fully completed for the selected date and mess.');
                }

                if ($baseQuantity > $pendingQty) {
                    throw new \RuntimeException('Issue quantity cannot exceed pending target quantity of '.number_format($pendingQty, 3).'.');
                }

                $currentBalance = $this->inventoryService->balanceForItem($item->id);
                if ($baseQuantity > $currentBalance) {
                    throw new \RuntimeException('Not enough stock to post this kitchen issue. Current balance: '.number_format($currentBalance, 3).' '.$item->uom);
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

                $newIssuedQty = round($issuedQty + $baseQuantity, 3);
                $target->update([
                    'issued_qty' => $newIssuedQty,
                    'status' => $newIssuedQty >= $requiredQty ? KitchenIssueTarget::STATUS_COMPLETED : KitchenIssueTarget::STATUS_OPEN,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['item_id' => $e->getMessage()])->withInput();
        }

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
