<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\KitchenIssue;
use App\Models\MealPlan;
use App\Models\Menu;
use App\Models\Recipe;
use App\Models\StockTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    public function index()
    {
        $items = Item::query()->orderBy('name')->get();
        $menus = Menu::query()->latest()->get();
        $recipes = Recipe::query()->latest()->limit(200)->get();
        $plans = MealPlan::query()->latest('plan_date')->limit(200)->get();
        $issues = KitchenIssue::query()->latest('issue_date')->limit(200)->get();
        $consumption = KitchenIssue::query()->selectRaw('item_id, sum(quantity) total_qty')->groupBy('item_id')->get();

        return view('admin.kitchen.index', compact('items', 'menus', 'recipes', 'plans', 'issues', 'consumption'));
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
        ]);

        $issue = KitchenIssue::query()->create($d + ['remarks' => $request->input('remarks')]);

        StockTransaction::query()->create([
            'item_id' => $d['item_id'],
            'txn_type' => 'KITCHEN_ISSUE',
            'quantity' => $d['quantity'],
            'unit_cost' => 0,
            'reference_type' => KitchenIssue::class,
            'reference_id' => $issue->id,
            'txn_at' => $d['issue_date'],
            'remarks' => $request->input('remarks', 'Kitchen issue'),
        ]);

        return back()->with('success', 'Kitchen issue posted');
    }

    public function approveIssue(KitchenIssue $issue): RedirectResponse
    {
        $issue->touch();

        return back()->with('success', 'Kitchen issue approval acknowledged. Stock was already posted on issue create; no extra approval side-effect exists in current schema.');
    }
}
