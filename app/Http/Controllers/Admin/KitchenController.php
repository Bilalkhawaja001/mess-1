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

class KitchenController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function index()
    {
        $items = Item::query()->with('units')->orderBy('name')->get();
        $menus = Menu::query()->latest()->get();
        $recipes = Recipe::query()->latest()->limit(200)->get();
        $plans = MealPlan::query()->latest('plan_date')->limit(200)->get();
        $issues = KitchenIssue::query()->latest('issue_date')->limit(200)->get();
        $consumption = KitchenIssue::query()->selectRaw('item_id, sum(quantity) total_qty')->groupBy('item_id')->get();
        $messes = Mess::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.kitchen.index', compact('items', 'menus', 'recipes', 'plans', 'issues', 'consumption', 'messes'));
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
        MealPlan::query()->create($request->validate([
            'plan_date' => 'required|date',
            'menu_id' => 'required|exists:menus,id',
            'planned_servings' => 'required|integer|min:1',
        ]));

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
        $plan->touch();

        return back()->with('success', 'Meal plan approval acknowledged. No inventory/accounting side-effect exists in current schema.');
    }

    public function issue(Request $request): RedirectResponse
    {
        $d = $request->validate([
            'issue_date' => 'required|date',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|numeric|min:0.001',
            'unit_code' => 'nullable|string|max:20',
            'issue_type' => 'required|string|in:CONSUMPTION,WASTAGE,DAMAGE,EXPIRED',
            'mess_id' => 'nullable|exists:messes,id',
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

        // Prevent negative stock before posting any outward transaction.
        $currentBalance = $this->inventoryService->balanceForItem($item->id);
        if ($baseQuantity > $currentBalance) {
            return back()
                ->withErrors(['quantity' => 'Not enough stock to post this kitchen issue. Current balance: '.number_format($currentBalance, 3).' '.$item->uom])
                ->withInput();
        }

        // Lightweight duplicate guard: block very recent identical issues from being posted twice.
        $recentDuplicate = KitchenIssue::query()
            ->where('item_id', $item->id)
            ->where('issue_date', $d['issue_date'])
            ->where('quantity', $baseQuantity)
            ->where('issue_type', $d['issue_type'])
            ->where('mess_id', $d['mess_id'] ?? null)
            ->where('remarks', $request->input('remarks'))
            ->where('created_at', '>=', now()->subMinutes(2))
            ->exists();

        if ($recentDuplicate) {
            return back()->with('info', 'Similar kitchen issue was just posted. Duplicate posting has been skipped.');
        }

        $issue = KitchenIssue::query()->create([
            'issue_date' => $d['issue_date'],
            'item_id' => $item->id,
            'quantity' => $baseQuantity,
            'mess_id' => $d['mess_id'] ?? null,
            'issue_type' => $d['issue_type'],
            'remarks' => $request->input('remarks'),
        ]);

        StockTransaction::query()->create([
            'item_id' => $item->id,
            'txn_type' => 'KITCHEN_ISSUE',
            'quantity' => $baseQuantity,
            'unit_cost' => 0,
            'trans_unit_code' => $transUnitCode,
            'trans_quantity' => $transQty,
            'reference_type' => KitchenIssue::class,
            'reference_id' => $issue->id,
            'txn_at' => $d['issue_date'],
            'remarks' => $request->input('remarks', 'Kitchen issue') ?: sprintf(
                'Kitchen issue (%s%s)',
                $d['issue_type'],
                $d['mess_id'] ? ', Mess: '.$issue->mess?->name : ''
            ),
        ]);

        return back()->with('success', 'Kitchen issue posted');
    }

    public function approveIssue(KitchenIssue $issue): RedirectResponse
    {
        $issue->touch();

        return back()->with('success', 'Kitchen issue approval acknowledged. Stock was already posted on issue create; no extra approval side-effect exists in current schema.');
    }
}
