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
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class KitchenController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function index(Request $request)
    {
        $activeTab = trim((string) $request->query('tab', 'issue'));
        if (! in_array($activeTab, ['issue', 'issues', 'ledger', 'menu', 'plans', 'consumption-report'], true)) {
            $activeTab = 'issue';
        }
        if ($activeTab === 'issues') {
            $activeTab = 'ledger';
        }

        $items = Item::query()->with('units')->orderBy('name')->get();
        $itemBalances = $this->inventoryService->balancesForItems($items->pluck('id')->all());
        $issueItems = $items
            ->where('is_active', true)
            ->map(function (Item $item) use ($itemBalances) {
                $availableQty = (float) ($itemBalances[$item->id] ?? 0);
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
            $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable $e) {
            $selectedMonth = now()->format('Y-m');
            $monthStart = now()->startOfMonth();
        }
        $monthEnd = $monthStart->copy()->endOfMonth();

        $fromDate = (string) ($request->query('from_date') ?: $monthStart->toDateString());
        $toDate = (string) ($request->query('to_date') ?: $monthEnd->toDateString());

        try {
            $fromDateCarbon = Carbon::parse($fromDate)->startOfDay();
        } catch (\Throwable $e) {
            $fromDateCarbon = $monthStart->copy()->startOfDay();
            $fromDate = $fromDateCarbon->toDateString();
        }

        try {
            $toDateCarbon = Carbon::parse($toDate)->endOfDay();
        } catch (\Throwable $e) {
            $toDateCarbon = $monthEnd->copy()->endOfDay();
            $toDate = $toDateCarbon->toDateString();
        }

        $consumptionItemId = trim((string) $request->query('item_id', ''));
        $consumptionCategory = trim((string) $request->query('category', ''));

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
            ->whereBetween('st.txn_at', [$fromDateCarbon->toDateTimeString(), $toDateCarbon->toDateTimeString()])
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

        $monthCycleExpression = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', st.txn_at)"
            : "DATE_FORMAT(st.txn_at, '%Y-%m')";

        $kitchenMonthlySummary = (clone $kitchenLedgerBase)
            ->groupBy(DB::raw($monthCycleExpression))
            ->orderByDesc(DB::raw($monthCycleExpression))
            ->limit(12)
            ->get([
                DB::raw($monthCycleExpression.' as month_cycle'),
                DB::raw('COUNT(*) as ledger_rows'),
                DB::raw('SUM(st.quantity) as total_qty'),
                DB::raw('SUM(st.quantity * st.unit_cost) as total_amount'),
            ]);

        $consumptionReportBase = (clone $kitchenLedgerBase)
            ->join('items as ci', 'ci.id', '=', 'st.item_id')
            ->whereBetween('st.txn_at', [$fromDateCarbon->toDateTimeString(), $toDateCarbon->toDateTimeString()]);

        if ($consumptionItemId !== '') {
            $consumptionReportBase->where('ci.id', (int) $consumptionItemId);
        }
        if ($consumptionCategory !== '') {
            $consumptionReportBase->where('ci.category', 'like', "%{$consumptionCategory}%");
        }

        $consumptionReportRows = (clone $consumptionReportBase)
            ->groupBy('ci.id', 'ci.sku', 'ci.name', 'ci.category', 'ci.uom')
            ->orderBy('ci.name')
            ->get([
                'ci.id as item_id',
                'ci.sku as item_sku',
                'ci.name as item_name',
                'ci.category as item_category',
                'ci.uom as item_uom',
                DB::raw('SUM(ABS(st.quantity)) as total_quantity'),
                DB::raw('SUM(ABS(st.quantity) * st.unit_cost) as total_amount'),
                DB::raw('MIN(st.unit_cost) as min_unit_cost'),
                DB::raw('MAX(st.unit_cost) as max_unit_cost'),
                DB::raw('MIN(ki.id) as first_issue_id'),
                DB::raw('MIN(st.id) as first_stock_txn_id'),
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
            'fromDate',
            'toDate',
            'kitchenMonthlySummary',
            'kitchenLedgerRows',
            'consumptionReportRows',
            'consumptionItemId',
            'consumptionCategory',
            'activeTab'
        ));
    }

    public function exportLedgerConsumption(Request $request): Response
    {
        [$fromDate, $toDate] = $this->resolveLedgerDateRange($request);

        $rows = $this->kitchenLedgerExportBaseQuery($fromDate, $toDate)
            ->orderBy('st.txn_at')
            ->orderBy('st.id')
            ->get([
                'st.txn_at',
                'ki.issue_date',
                'ki.issue_type',
                'm.name as mess_name',
                'i.sku as item_sku',
                'i.name as item_name',
                'i.category as item_category',
                'i.uom as item_uom',
                'st.unit_cost',
                'st.quantity',
                DB::raw('(ABS(st.quantity) * st.unit_cost) as total_amount'),
                'st.remarks',
            ]);

        $csvRows = [[
            'Issue Date', 'Ledger Date', 'Mess', 'Issue Type', 'Item Code / SKU', 'Item Name', 'Category', 'Unit', 'Quantity', 'Unit Cost', 'Total Amount', 'Remarks',
        ]];

        foreach ($rows as $row) {
            $csvRows[] = [
                optional($row->issue_date)->format('Y-m-d') ?: (string) $row->issue_date,
                optional($row->txn_at)->format('Y-m-d H:i:s'),
                $row->mess_name,
                $row->issue_type,
                $row->item_sku,
                $row->item_name,
                $row->item_category,
                $row->item_uom,
                number_format(abs((float) $row->quantity), 3, '.', ''),
                number_format((float) $row->unit_cost, 2, '.', ''),
                number_format((float) $row->total_amount, 2, '.', ''),
                $row->remarks,
            ];
        }

        return $this->csvDownloadResponse('kitchen_detailed_consumption.csv', $csvRows);
    }

    public function exportLedgerConsumptionSummary(Request $request): Response
    {
        [$fromDate, $toDate] = $this->resolveLedgerDateRange($request);

        $rows = $this->kitchenLedgerExportBaseQuery($fromDate, $toDate)
            ->groupBy('i.id', 'i.sku', 'i.name', 'i.category', 'i.uom')
            ->orderBy('i.name')
            ->get([
                'i.sku as item_sku',
                'i.name as item_name',
                'i.category as item_category',
                'i.uom as item_uom',
                DB::raw('SUM(ABS(st.quantity)) as total_quantity'),
                DB::raw('SUM(ABS(st.quantity) * st.unit_cost) as total_amount'),
                DB::raw('MIN(DATE(ki.issue_date)) as first_issue_date'),
                DB::raw('MAX(DATE(ki.issue_date)) as last_issue_date'),
            ]);

        $csvRows = [[
            'Item Code / SKU', 'Item Name', 'Category', 'Unit', 'Total Quantity', 'Average Unit Cost', 'Total Amount', 'First Issue Date', 'Last Issue Date',
        ]];

        foreach ($rows as $row) {
            $totalQty = (float) $row->total_quantity;
            $totalAmount = (float) $row->total_amount;
            $avgCost = $totalQty > 0 ? ($totalAmount / $totalQty) : 0;

            $csvRows[] = [
                $row->item_sku,
                $row->item_name,
                $row->item_category,
                $row->item_uom,
                number_format($totalQty, 3, '.', ''),
                number_format($avgCost, 2, '.', ''),
                number_format($totalAmount, 2, '.', ''),
                $row->first_issue_date,
                $row->last_issue_date,
            ];
        }

        return $this->csvDownloadResponse('kitchen_item_summary_consumption.csv', $csvRows);
    }

    public function exportConsumptionReport(Request $request): Response
    {
        [$fromDate, $toDate] = $this->resolveLedgerDateRange($request);
        $itemId = trim((string) $request->query('item_id', ''));
        $category = trim((string) $request->query('category', ''));

        $rows = $this->kitchenLedgerExportBaseQuery($fromDate, $toDate)
            ->join('items as ci', 'ci.id', '=', 'st.item_id');

        if ($itemId !== '') {
            $rows->where('ci.id', (int) $itemId);
        }
        if ($category !== '') {
            $rows->where('ci.category', 'like', "%{$category}%");
        }

        $rows = $rows
            ->groupBy('ci.id', 'ci.sku', 'ci.name', 'ci.category', 'ci.uom')
            ->orderBy('ci.name')
            ->get([
                'ci.sku as item_sku',
                'ci.name as item_name',
                'ci.category as item_category',
                'ci.uom as item_uom',
                DB::raw('SUM(ABS(st.quantity)) as total_quantity'),
                DB::raw('SUM(ABS(st.quantity) * st.unit_cost) as total_amount'),
                DB::raw('MIN(st.unit_cost) as min_unit_cost'),
                DB::raw('MAX(st.unit_cost) as max_unit_cost'),
                DB::raw('MIN(ki.id) as first_issue_id'),
                DB::raw('MIN(st.id) as first_stock_txn_id'),
            ]);

        $csvRows = [[
            'Item Code / SKU', 'Item Name', 'Category', 'UOM', 'Qty Consumed', 'Min Unit Cost', 'Max Unit Cost', 'Total Amount', 'Kitchen Issue Ref', 'Stock Txn Ref',
        ]];

        foreach ($rows as $row) {
            $csvRows[] = [
                $row->item_sku,
                $row->item_name,
                $row->item_category,
                $row->item_uom,
                number_format((float) $row->total_quantity, 3, '.', ''),
                number_format((float) $row->min_unit_cost, 2, '.', ''),
                number_format((float) $row->max_unit_cost, 2, '.', ''),
                number_format((float) $row->total_amount, 2, '.', ''),
                $row->first_issue_id ? 'KitchenIssue #'.$row->first_issue_id : '',
                $row->first_stock_txn_id ? 'StockTransaction #'.$row->first_stock_txn_id : '',
            ];
        }

        return $this->csvDownloadResponse('consumption_report.csv', $csvRows);
    }

    public function apiMenus(): JsonResponse
    {
        $rows = Menu::query()
            ->whereIn('status', [Menu::STATUS_DRAFT, Menu::STATUS_APPROVED])
            ->orderBy('title')
            ->get(['id', 'title', 'meal_type'])
            ->map(fn (Menu $menu) => [
                'id' => $menu->id,
                'name' => $menu->title,
                'meal_type' => $menu->meal_type,
            ]);

        return response()->json(['menus' => $rows]);
    }

    public function storeMenu(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'meal_type' => 'required|string|max:30',
        ]);

        Menu::query()->create([
            'menu_date' => now()->toDateString(),
            'meal_type' => $data['meal_type'],
            'title' => $data['name'],
            'description' => null,
            'items_text' => $data['name'],
            'status' => Menu::STATUS_DRAFT,
        ]);

        return back()->with('success', 'Menu created');
    }

    public function updateMenu(Request $request, Menu $menu): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'meal_type' => 'required|string|max:30',
            'is_active' => ['nullable', Rule::in(['0', '1', 0, 1, true, false])],
        ]);

        $menu->update([
            'meal_type' => $data['meal_type'],
            'title' => $data['name'],
            'items_text' => $menu->items_text ?: $data['name'],
            'status' => array_key_exists('is_active', $data) && ! (bool) $data['is_active']
                ? Menu::STATUS_INACTIVE
                : ($menu->status === Menu::STATUS_INACTIVE ? Menu::STATUS_DRAFT : $menu->status),
        ]);

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

        $availableQty = $this->inventoryService->balanceForItem($item->id);

        if ($availableQty <= 0) {
            return back()
                ->withErrors(['quantity' => 'Stock not available.'])
                ->withInput();
        }

        if ($baseQuantity > $availableQty) {
            return back()
                ->withErrors([
                    'quantity' => 'Not enough stock available. Available: '.number_format($availableQty, 3).' '.$item->uom.', Requested: '.number_format($baseQuantity, 3).' '.$item->uom,
                ])
                ->withInput();
        }

        $pendingDuplicate = KitchenIssue::query()
            ->where('item_id', $item->id)
            ->where('issue_date', $d['issue_date'])
            ->where('quantity', $baseQuantity)
            ->where('issue_type', $d['issue_type'])
            ->where('mess_id', $d['mess_id'])
            ->whereIn('status', [KitchenIssue::STATUS_DRAFT, 'pending'])
            ->exists();

        if ($pendingDuplicate) {
            return back()->withErrors(['kitchen_issue' => 'A similar pending kitchen issue already exists.'])->withInput();
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
                if ($currentBalance <= 0) {
                    throw new \RuntimeException('Stock not available.');
                }

                if ((float) $lockedIssue->quantity > $currentBalance) {
                    throw new \RuntimeException('Not enough stock available. Available: '.number_format($currentBalance, 3).' '.$item->uom.', Requested: '.number_format((float) $lockedIssue->quantity, 3).' '.$item->uom);
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

    private function kitchenLedgerExportBaseQuery(Carbon $fromDate, Carbon $toDate)
    {
        return StockTransaction::query()
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
            ->whereNotNull('ki.approved_stock_txn_id')
            ->whereBetween('st.txn_at', [$fromDate->toDateTimeString(), $toDate->toDateTimeString()]);
    }

    private function resolveLedgerDateRange(Request $request): array
    {
        $defaultStart = now()->startOfMonth();
        $defaultEnd = now()->endOfMonth();

        try {
            $fromDate = Carbon::parse((string) $request->query('from_date', $defaultStart->toDateString()))->startOfDay();
        } catch (\Throwable $e) {
            $fromDate = $defaultStart;
        }

        try {
            $toDate = Carbon::parse((string) $request->query('to_date', $defaultEnd->toDateString()))->endOfDay();
        } catch (\Throwable $e) {
            $toDate = $defaultEnd;
        }

        if ($toDate->lt($fromDate)) {
            [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
        }

        return [$fromDate, $toDate];
    }

    private function csvDownloadResponse(string $filename, array $rows): Response
    {
        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function normalizeKitchenTab(string $tab): string
    {
        $tab = Str::lower(trim($tab));

        return match ($tab) {
            'issues', 'ledger' => 'ledger',
            'consumption-report' => 'consumption-report',
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
