<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\Item;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Models\StockTransaction;
use App\Models\VendorReturn;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $activeTab = trim((string) $request->query('tab', 'items'));
        $stockLedgerFromDate = trim((string) $request->query('from_date', ''));
        $stockLedgerToDate = trim((string) $request->query('to_date', ''));
        $stockLedgerTxnType = trim((string) $request->query('txn_type', ''));
        $stockLedgerReferenceType = trim((string) $request->query('reference_type', ''));
        $stockLedgerItemId = trim((string) $request->query('item_id', ''));
        $stockLedgerCategory = trim((string) $request->query('category', ''));
        $selectedStockCountId = (int) $request->query('stock_count_id', 0);

        if (! in_array($activeTab, ['items', 'store-stock', 'vendor-return', 'stock-ledger', 'stock-count'], true)) {
            $activeTab = 'items';
        }

        $items = Item::query()
            ->with('units')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('uom', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();
        $ledger = StockTransaction::query()->latest('txn_at')->limit(100)->get();
        $balances = $this->inventoryService->stockBalances();
        $lowStockItems = $this->inventoryService->lowStockItems();

        $ledgerByItem = $ledger->groupBy('item_id');
        $storeStockRows = collect($balances)
            ->map(function (array $row) use ($ledgerByItem) {
                /** @var \App\Models\Item $item */
                $item = $row['item'];
                $itemLedger = collect($ledgerByItem->get($item->id, collect()));
                $receivedQty = (float) $itemLedger
                    ->whereIn('txn_type', ['OPENING', 'IN', 'ADJUSTMENT', 'GRN'])
                    ->sum('quantity');
                $issuedQty = (float) $itemLedger
                    ->whereIn('txn_type', ['OUT', 'KITCHEN_ISSUE', 'VENDOR_RETURN'])
                    ->sum('quantity');
                $latestMovement = $itemLedger->sortByDesc('txn_at')->first();

                return [
                    'item' => $item,
                    'balance' => (float) ($row['balance'] ?? 0),
                    'received_qty' => $receivedQty,
                    'issued_qty' => $issuedQty,
                    'latest_movement' => $latestMovement,
                ];
            })
            ->filter(function (array $row) use ($search) {
                if ($search === '') {
                    return true;
                }

                $item = $row['item'];
                $haystack = strtolower(implode(' ', [
                    (string) $item->name,
                    (string) $item->sku,
                    (string) ($item->category ?? ''),
                    (string) $item->uom,
                    (string) (optional($row['latest_movement'])->remarks ?? ''),
                ]));

                return str_contains($haystack, strtolower($search));
            })
            ->sortBy(fn (array $row) => strtolower((string) $row['item']->name))
            ->values();

        $returnSourceGrns = GoodsReceipt::query()
            ->with(['purchaseOrder.vendor', 'lines.item.units'])
            ->latest('received_date')
            ->limit(200)
            ->get();

        $returnSourceLineIds = $returnSourceGrns
            ->flatMap(fn (GoodsReceipt $grn) => $grn->lines->pluck('id'))
            ->values();

        $returnSourceTxns = StockTransaction::query()
            ->where('reference_type', GoodsReceiptLine::class)
            ->where('txn_type', 'GRN')
            ->whereIn('reference_id', $returnSourceLineIds)
            ->get()
            ->keyBy('reference_id');

        $returnedQtyBySource = VendorReturn::query()
            ->selectRaw("CONCAT(goods_receipt_id, ':', item_id) as source_key, SUM(qty_returned) as total_returned")
            ->groupBy('goods_receipt_id', 'item_id')
            ->pluck('total_returned', 'source_key');

        $vendorReturnSources = $returnSourceGrns
            ->flatMap(function (GoodsReceipt $grn) use ($returnSourceTxns, $returnedQtyBySource) {
                $vendor = $grn->purchaseOrder?->vendor;

                if (! $vendor) {
                    return collect();
                }

                return $grn->lines->map(function (GoodsReceiptLine $line) use ($grn, $vendor, $returnSourceTxns, $returnedQtyBySource) {
                    $item = $line->item;
                    if (! $item) {
                        return null;
                    }

                    $txn = $returnSourceTxns->get($line->id);
                    if (! $txn) {
                        return null;
                    }

                    $currentBalance = $this->inventoryService->balanceForItem($item->id);
                    $receivedBaseQty = (float) $txn->quantity;
                    $sourceKey = $grn->id.':'.$item->id;
                    $alreadyReturnedBaseQty = (float) ($returnedQtyBySource[$sourceKey] ?? 0);
                    $sourcePendingQty = max($receivedBaseQty - $alreadyReturnedBaseQty, 0);
                    $returnableQty = min($currentBalance, $sourcePendingQty);

                    if ($returnableQty <= 0) {
                        return null;
                    }

                    return [
                        'goods_receipt_id' => $grn->id,
                        'goods_receipt_line_id' => $line->id,
                        'grn_number' => $grn->grn_number,
                        'received_date' => $grn->received_date,
                        'vendor_id' => $vendor->id,
                        'vendor_name' => $vendor->name,
                        'item_id' => $item->id,
                        'item_name' => $item->name,
                        'item_sku' => $item->sku,
                        'uom' => $item->uom,
                        'unit_cost' => (float) $txn->unit_cost,
                        'source_received_qty' => $receivedBaseQty,
                        'already_returned_qty' => $alreadyReturnedBaseQty,
                        'current_balance_qty' => $currentBalance,
                        'returnable_qty' => $returnableQty,
                        'units' => $item->units->map(fn ($u) => [
                            'code' => $u->unit_code,
                            'factor' => (float) $u->factor_to_base,
                        ])->values()->all(),
                    ];
                })->filter();
            })
            ->filter()
            ->filter(function (array $source) use ($search) {
                if ($search === '') {
                    return true;
                }

                $haystack = strtolower(implode(' ', [
                    (string) ($source['grn_number'] ?? ''),
                    (string) ($source['vendor_name'] ?? ''),
                    (string) ($source['item_sku'] ?? ''),
                    (string) ($source['item_name'] ?? ''),
                ]));

                return str_contains($haystack, strtolower($search));
            })
            ->sortByDesc('received_date')
            ->values();

        $vendorReturns = VendorReturn::query()
            ->with(['vendor', 'goodsReceipt', 'item'])
            ->latest('return_date')
            ->limit(50)
            ->get();

        $stockLedgerQuery = StockTransaction::query()
            ->from('stock_transactions as st')
            ->leftJoin('items as i', 'i.id', '=', 'st.item_id')
            ->select([
                'st.id',
                'st.txn_at',
                'st.txn_type',
                'st.quantity',
                'st.unit_cost',
                'st.trans_unit_code',
                'st.trans_quantity',
                'st.reference_type',
                'st.reference_id',
                'st.remarks',
                'i.id as item_id',
                'i.sku as item_sku',
                'i.name as item_name',
                'i.category as item_category',
                'i.uom as item_uom',
            ]);

        if ($search !== '') {
            $stockLedgerQuery->where(function ($query) use ($search) {
                $query->where('i.sku', 'like', "%{$search}%")
                    ->orWhere('i.name', 'like', "%{$search}%")
                    ->orWhere('i.category', 'like', "%{$search}%")
                    ->orWhere('st.txn_type', 'like', "%{$search}%")
                    ->orWhere('st.reference_type', 'like', "%{$search}%")
                    ->orWhere('st.remarks', 'like', "%{$search}%");
            });
        }
        if ($stockLedgerFromDate !== '') {
            $stockLedgerQuery->whereDate('st.txn_at', '>=', $stockLedgerFromDate);
        }
        if ($stockLedgerToDate !== '') {
            $stockLedgerQuery->whereDate('st.txn_at', '<=', $stockLedgerToDate);
        }
        if ($stockLedgerTxnType !== '') {
            $stockLedgerQuery->where('st.txn_type', $stockLedgerTxnType);
        }
        if ($stockLedgerReferenceType !== '') {
            $stockLedgerQuery->where('st.reference_type', 'like', "%{$stockLedgerReferenceType}%");
        }
        if ($stockLedgerItemId !== '') {
            $stockLedgerQuery->where('i.id', (int) $stockLedgerItemId);
        }
        if ($stockLedgerCategory !== '') {
            $stockLedgerQuery->where('i.category', 'like', "%{$stockLedgerCategory}%");
        }

        $stockLedgerRows = $stockLedgerQuery
            ->orderByDesc('st.txn_at')
            ->orderByDesc('st.id')
            ->limit(300)
            ->get();

        $stockLedgerTxnTypes = StockTransaction::query()
            ->select('txn_type')
            ->distinct()
            ->orderBy('txn_type')
            ->pluck('txn_type');

        $stockLedgerReferenceTypes = StockTransaction::query()
            ->whereNotNull('reference_type')
            ->select('reference_type')
            ->distinct()
            ->orderBy('reference_type')
            ->pluck('reference_type');

        $stockCountHistory = StockCount::query()
            ->with(['createdBy', 'postedBy'])
            ->orderByDesc('count_date')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        $selectedStockCount = $selectedStockCountId > 0
            ? StockCount::query()->with(['lines.item', 'createdBy', 'postedBy'])->find($selectedStockCountId)
            : $stockCountHistory->first();

        return view('admin.inventory.index', compact(
            'items',
            'ledger',
            'balances',
            'lowStockItems',
            'storeStockRows',
            'vendorReturnSources',
            'vendorReturns',
            'search',
            'activeTab',
            'stockLedgerRows',
            'stockLedgerFromDate',
            'stockLedgerToDate',
            'stockLedgerTxnType',
            'stockLedgerReferenceType',
            'stockLedgerItemId',
            'stockLedgerCategory',
            'stockLedgerTxnTypes',
            'stockLedgerReferenceTypes',
            'stockCountHistory',
            'selectedStockCount'
        ));
    }

    public function exportStockLedger(Request $request): StreamedResponse
    {
        $search = trim((string) $request->query('q', ''));
        $fromDate = trim((string) $request->query('from_date', ''));
        $toDate = trim((string) $request->query('to_date', ''));
        $txnType = trim((string) $request->query('txn_type', ''));
        $referenceType = trim((string) $request->query('reference_type', ''));
        $itemId = trim((string) $request->query('item_id', ''));
        $category = trim((string) $request->query('category', ''));
        $remarks = trim((string) $request->query('remarks', ''));

        $rows = StockTransaction::query()
            ->from('stock_transactions as st')
            ->leftJoin('items as i', 'i.id', '=', 'st.item_id')
            ->select([
                'st.txn_at',
                'st.txn_type',
                'st.quantity',
                'st.unit_cost',
                'st.trans_unit_code',
                'st.reference_type',
                'st.reference_id',
                'st.remarks',
                'i.sku as item_sku',
                'i.name as item_name',
                'i.category as item_category',
                'i.uom as item_uom',
            ]);

        if ($search !== '') {
            $rows->where(function ($query) use ($search) {
                $query->where('i.sku', 'like', "%{$search}%")
                    ->orWhere('i.name', 'like', "%{$search}%")
                    ->orWhere('i.category', 'like', "%{$search}%")
                    ->orWhere('st.txn_type', 'like', "%{$search}%")
                    ->orWhere('st.reference_type', 'like', "%{$search}%")
                    ->orWhere('st.remarks', 'like', "%{$search}%");
            });
        }
        if ($fromDate !== '') {
            $rows->whereDate('st.txn_at', '>=', $fromDate);
        }
        if ($toDate !== '') {
            $rows->whereDate('st.txn_at', '<=', $toDate);
        }
        if ($txnType !== '') {
            $rows->where('st.txn_type', $txnType);
        }
        if ($referenceType !== '') {
            $rows->where('st.reference_type', 'like', "%{$referenceType}%");
        }
        if ($itemId !== '') {
            $rows->where('i.id', (int) $itemId);
        }
        if ($category !== '') {
            $rows->where('i.category', 'like', "%{$category}%");
        }
        if ($remarks !== '') {
            $rows->where('st.remarks', 'like', "%{$remarks}%");
        }

        $rows = $rows->orderBy('st.txn_at')->orderBy('st.id')->get();

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Item Code', 'Item Name', 'Type', 'Qty', 'UOM', 'Unit Cost', 'Reference', 'Remarks']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    optional($row->txn_at)->format('Y-m-d H:i:s'),
                    $row->item_sku,
                    $row->item_name,
                    $row->txn_type,
                    number_format((float) $row->quantity, 3, '.', ''),
                    $row->trans_unit_code ?: $row->item_uom,
                    number_format((float) $row->unit_cost, 2, '.', ''),
                    trim((string) class_basename((string) $row->reference_type).' #'.((string) $row->reference_id)),
                    $row->remarks,
                ]);
            }

            fclose($handle);
        }, 'stock_ledger.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function storeStockCount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'count_date' => 'required|date',
            'remarks' => 'nullable|string|max:1000',
            'counted_qty' => 'required|array|min:1',
            'counted_qty.*' => 'nullable|numeric|min:0',
            'line_remarks' => 'nullable|array',
            'line_remarks.*' => 'nullable|string|max:1000',
        ]);

        $balances = collect($this->inventoryService->stockBalances())->keyBy(fn (array $row) => (int) $row['item']->id);
        $itemIds = array_map('intval', array_keys($data['counted_qty'] ?? []));
        $items = Item::query()->whereIn('id', $itemIds)->orderBy('name')->get()->keyBy('id');

        if ($items->isEmpty()) {
            return back()->withErrors(['counted_qty' => 'At least one item is required for stock count.'])->withInput();
        }

        DB::transaction(function () use ($data, $balances, $items) {
            $stockCount = StockCount::query()->create([
                'count_date' => $data['count_date'],
                'status' => 'DRAFT',
                'remarks' => $data['remarks'] ?? null,
                'created_by' => optional(auth()->user())->id,
            ]);

            foreach ($items as $itemId => $item) {
                $systemQty = (float) (($balances->get((int) $itemId)['balance'] ?? 0));
                $countedQty = (float) ($data['counted_qty'][$itemId] ?? 0);

                StockCountLine::query()->create([
                    'stock_count_id' => $stockCount->id,
                    'item_id' => $item->id,
                    'system_qty' => $systemQty,
                    'counted_qty' => $countedQty,
                    'variance_qty' => round($countedQty - $systemQty, 3),
                    'remarks' => $data['line_remarks'][$itemId] ?? null,
                ]);
            }
        });

        return redirect()->route('admin.inventory.index', ['tab' => 'stock-count'])
            ->with('success', 'Stock count session created in DRAFT status.');
    }

    public function showStockCount(StockCount $stockCount)
    {
        $stockCount->load(['lines.item', 'createdBy', 'postedBy']);

        return view('admin.inventory.index', [
            'items' => Item::query()->with('units')->orderBy('name')->get(),
            'ledger' => StockTransaction::query()->latest('txn_at')->limit(100)->get(),
            'balances' => $this->inventoryService->stockBalances(),
            'lowStockItems' => $this->inventoryService->lowStockItems(),
            'storeStockRows' => collect(),
            'vendorReturnSources' => collect(),
            'vendorReturns' => collect(),
            'search' => '',
            'activeTab' => 'stock-count',
            'stockLedgerRows' => collect(),
            'stockLedgerFromDate' => '',
            'stockLedgerToDate' => '',
            'stockLedgerTxnType' => '',
            'stockLedgerReferenceType' => '',
            'stockLedgerItemId' => '',
            'stockLedgerCategory' => '',
            'stockLedgerTxnTypes' => collect(),
            'stockLedgerReferenceTypes' => collect(),
            'stockCountHistory' => StockCount::query()->with(['createdBy', 'postedBy'])->orderByDesc('count_date')->orderByDesc('id')->limit(30)->get(),
            'selectedStockCount' => $stockCount,
        ]);
    }

    public function postStockCount(StockCount $stockCount): RedirectResponse
    {
        if ($stockCount->status === 'POSTED') {
            return back()->withErrors(['stock_count' => 'Stock count already posted.']);
        }

        $stockCount->update([
            'status' => 'POSTED',
            'posted_by' => optional(auth()->user())->id,
            'posted_at' => now(),
        ]);

        return redirect()->route('admin.inventory.stock-counts.show', $stockCount)
            ->with('success', 'Stock count marked as POSTED. No stock transaction was created.');
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'item_code' => 'nullable|string|max:255',
            'item_name' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'uom' => 'required|string|max:20',
            'reorder_level' => 'nullable|numeric|min:0',
            // backward compatibility with old form fields
            'sku' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
        ]);

        $sku = trim((string) ($data['item_code'] ?? $data['sku'] ?? ''));
        $name = trim((string) ($data['item_name'] ?? $data['name'] ?? ''));

        if ($sku === '' || $name === '') {
            return back()->withErrors([
                'item_code' => 'ItemCode and ItemName are required.',
            ])->withInput();
        }

        Item::query()->create([
            'sku' => $sku,
            'name' => $name,
            'category' => $data['category'] ?? 'Uncategorized',
            'uom' => $data['uom'],
            'reorder_level' => $data['reorder_level'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Item created successfully.');
    }

    public function updateItem(Request $request, Item $item): RedirectResponse
    {
        $data = $request->validate([
            'item_code' => 'required|string|max:255',
            'item_name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'uom' => 'required|string|max:20',
            'reorder_level' => 'nullable|numeric|min:0',
            'is_active' => 'required|boolean',
        ]);

        $item->update([
            'sku' => trim((string) $data['item_code']),
            'name' => trim((string) $data['item_name']),
            'category' => trim((string) ($data['category'] ?? '')) ?: 'Uncategorized',
            'uom' => trim((string) $data['uom']),
            'reorder_level' => $data['reorder_level'] ?? 0,
            'is_active' => (bool) $data['is_active'],
        ]);

        if ($item->uom) {
            $item->units()->firstOrCreate(
                ['unit_code' => $item->uom],
                [
                    'factor_to_base' => 1.0,
                    'is_default_for_grn' => true,
                    'is_default_for_kitchen' => true,
                ]
            );
        }

        return redirect()
            ->route('admin.inventory.index', ['tab' => 'items'])
            ->with('success', 'Item updated successfully.');
    }

    public function bulkUploadItems(Request $request): RedirectResponse
    {
        $request->validate([
            'items_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('items_file');
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return back()->withErrors(['items_file' => 'Unable to read file.']);
        }

        $header = fgetcsv($handle);
        if (!is_array($header)) {
            fclose($handle);
            return back()->withErrors(['items_file' => 'CSV header missing.']);
        }

        $normalizedHeader = array_map(static fn ($h) => strtolower(trim((string) $h)), $header);
        $required = ['itemcode', 'itemname', 'category', 'uom'];

        foreach ($required as $key) {
            if (!in_array($key, $normalizedHeader, true)) {
                fclose($handle);
                return back()->withErrors([
                    'items_file' => 'CSV must contain headers: ItemCode, ItemName, Category, UoM',
                ]);
            }
        }

        $index = array_flip($normalizedHeader);
        $rows = [];
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            $sku = trim((string) ($row[$index['itemcode']] ?? ''));
            $name = trim((string) ($row[$index['itemname']] ?? ''));
            $category = trim((string) ($row[$index['category']] ?? ''));
            $uom = trim((string) ($row[$index['uom']] ?? ''));

            if ($sku === '' && $name === '' && $category === '' && $uom === '') {
                continue;
            }

            if ($sku === '' || $name === '' || $uom === '') {
                fclose($handle);
                return back()->withErrors([
                    'items_file' => "Invalid data at line {$line}. ItemCode, ItemName and UoM are required.",
                ]);
            }

            $rows[] = [
                'sku' => $sku,
                'name' => $name,
                'category' => $category !== '' ? $category : 'Uncategorized',
                'uom' => $uom,
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        fclose($handle);

        if (count($rows) === 0) {
            return back()->withErrors(['items_file' => 'No valid rows found in CSV.']);
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $payload) {
                $existing = Item::query()->where('sku', $payload['sku'])->first();

                if ($existing) {
                    // Block UoM change if stock already exists for this item.
                    if ($existing->uom !== $payload['uom'] && StockTransaction::query()->where('item_id', $existing->id)->exists()) {
                        $payload['uom'] = $existing->uom;
                    }

                    $existing->update([
                        'name' => $payload['name'],
                        'category' => $payload['category'],
                        'uom' => $payload['uom'],
                        'is_active' => $payload['is_active'],
                        'updated_at' => $payload['updated_at'],
                    ]);
                } else {
                    Item::query()->create($payload);
                }
            }
        });

        return back()->with('success', 'Bulk items upload completed.');
    }

    public function storeTxn(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'item_id' => 'required|exists:items,id',
            'txn_type' => 'required|in:OPENING,IN,OUT,ADJUSTMENT',
            'quantity' => 'required|numeric|min:0.001',
            'txn_at' => 'required|date',
            'unit_code' => 'nullable|string|max:20',
        ]);

        $item = Item::query()->with('units')->findOrFail($data['item_id']);
        $unitCode = $data['unit_code'] ?? null;
        $transQuantity = (float) $data['quantity'];

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

        // Prevent negative stock for OUT transactions.
        if (in_array($data['txn_type'], ['OUT'], true)) {
            $currentBalance = $this->inventoryService->balanceForItem($item->id);
            if ($baseQuantity > $currentBalance) {
                return back()
                    ->withErrors(['quantity' => 'Not enough stock to post this transaction. Current balance: '.number_format($currentBalance, 3).' '.$item->uom])
                    ->withInput();
            }
        }

        StockTransaction::query()->create([
            'item_id' => $item->id,
            'txn_type' => $data['txn_type'],
            'quantity' => $baseQuantity,
            'unit_cost' => $request->input('unit_cost', 0),
            'trans_unit_code' => $transUnitCode,
            'trans_quantity' => $transQty,
            'remarks' => $request->input('remarks'),
            'txn_at' => $data['txn_at'],
        ]);

        return back()->with('success', 'Stock transaction posted.');
    }

    public function storeVendorReturn(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'goods_receipt_id' => 'required|exists:goods_receipts,id',
            'item_id' => 'required|exists:items,id',
            'return_date' => 'required|date',
            'quantity' => 'required|numeric|min:0.001',
            'unit_code' => 'nullable|string|max:20',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $grn = GoodsReceipt::query()->with(['purchaseOrder.vendor', 'lines.item.units'])->findOrFail($data['goods_receipt_id']);
        $vendor = $grn->purchaseOrder?->vendor;
        $line = $grn->lines->firstWhere('item_id', (int) $data['item_id']) ?? $grn->lines->first();
        $item = $line?->item;

        if (! $vendor || (int) $vendor->id !== (int) $data['vendor_id']) {
            return back()->withErrors(['vendor_id' => 'Selected vendor does not match the selected GRN.'])->withInput();
        }

        if (! $line || ! $item || (int) $item->id !== (int) $data['item_id']) {
            return back()->withErrors(['item_id' => 'Selected item does not match the selected GRN source.'])->withInput();
        }

        $unitCode = trim((string) ($data['unit_code'] ?? ''));
        $transQuantity = (float) $data['quantity'];
        $baseQuantity = $transQuantity;
        $transUnitCode = null;
        $transQty = null;

        if ($unitCode !== '') {
            $unit = $item->units->firstWhere('unit_code', $unitCode);
            if (! $unit) {
                return back()->withErrors(['unit_code' => 'Invalid unit for selected item.'])->withInput();
            }

            $baseQuantity = $transQuantity * (float) $unit->factor_to_base;
            $transUnitCode = $unit->unit_code;
            $transQty = $transQuantity;
        }

        $sourceTxn = StockTransaction::query()
            ->where('reference_type', GoodsReceiptLine::class)
            ->where('reference_id', $line->id)
            ->where('txn_type', 'GRN')
            ->where('item_id', $item->id)
            ->latest('id')
            ->first();

        if (! $sourceTxn) {
            return back()->withErrors(['goods_receipt_id' => 'Selected GRN has no stock receipt trail.'])->withInput();
        }

        $currentBalance = $this->inventoryService->balanceForItem($item->id);
        $alreadyReturnedQty = (float) VendorReturn::query()
            ->where('goods_receipt_id', $grn->id)
            ->where('item_id', $item->id)
            ->sum('qty_returned');
        $sourceReceivedQty = (float) $sourceTxn->quantity;
        $sourcePendingQty = max($sourceReceivedQty - $alreadyReturnedQty, 0);
        $maxReturnableQty = min($currentBalance, $sourcePendingQty);

        if ($maxReturnableQty <= 0) {
            return back()->withErrors(['quantity' => 'This source has no returnable store stock left.'])->withInput();
        }

        if ($baseQuantity > $currentBalance) {
            return back()->withErrors(['quantity' => 'Return quantity cannot exceed current store stock. Current balance: '.number_format($currentBalance, 3).' '.$item->uom])->withInput();
        }

        if ($baseQuantity > $sourcePendingQty) {
            return back()->withErrors(['quantity' => 'Return quantity cannot exceed remaining returnable qty for selected GRN source.'])->withInput();
        }

        try {
            DB::transaction(function () use ($data, $grn, $item, $vendor, $baseQuantity, $transUnitCode, $transQty, $sourceTxn) {
                $lockedSourceTxn = StockTransaction::query()
                    ->whereKey($sourceTxn->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedCurrentIn = (float) StockTransaction::query()
                    ->where('item_id', $item->id)
                    ->lockForUpdate()
                    ->whereIn('txn_type', ['OPENING', 'IN', 'ADJUSTMENT', 'GRN'])
                    ->sum('quantity');
                $lockedCurrentOut = (float) StockTransaction::query()
                    ->where('item_id', $item->id)
                    ->lockForUpdate()
                    ->whereIn('txn_type', ['OUT', 'KITCHEN_ISSUE', 'VENDOR_RETURN'])
                    ->sum('quantity');
                $lockedCurrentBalance = round($lockedCurrentIn - $lockedCurrentOut, 3);
                $lockedAlreadyReturnedQty = (float) VendorReturn::query()
                    ->where('goods_receipt_id', $grn->id)
                    ->where('item_id', $item->id)
                    ->lockForUpdate()
                    ->sum('qty_returned');
                $lockedSourcePendingQty = max((float) $lockedSourceTxn->quantity - $lockedAlreadyReturnedQty, 0);

                if ($baseQuantity > $lockedCurrentBalance) {
                    throw new \RuntimeException('Return quantity cannot exceed current store stock.');
                }

                if ($baseQuantity > $lockedSourcePendingQty) {
                    throw new \RuntimeException('Return quantity cannot exceed remaining returnable qty for selected GRN source.');
                }

                $vendorReturn = VendorReturn::query()->create([
                    'vendor_id' => $vendor->id,
                    'goods_receipt_id' => $grn->id,
                    'item_id' => $item->id,
                    'return_number' => 'VRN-'.now()->format('YmdHis'),
                    'return_date' => $data['return_date'],
                    'qty_returned' => $baseQuantity,
                    'trans_unit_code' => $transUnitCode,
                    'trans_quantity' => $transQty,
                    'unit_cost' => (float) $lockedSourceTxn->unit_cost,
                    'remarks' => $data['remarks'] ?? null,
                ]);

                StockTransaction::query()->create([
                    'item_id' => $item->id,
                    'txn_type' => 'VENDOR_RETURN',
                    'quantity' => $baseQuantity,
                    'unit_cost' => (float) $lockedSourceTxn->unit_cost,
                    'trans_unit_code' => $transUnitCode,
                    'trans_quantity' => $transQty,
                    'reference_type' => VendorReturn::class,
                    'reference_id' => $vendorReturn->id,
                    'remarks' => 'Vendor return against '.$grn->grn_number,
                    'txn_at' => $data['return_date'],
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Vendor return posted. Stock reduced from store balance.');
    }

    public function importItems(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);
        $counts = ['inserted' => 0, 'updated' => 0, 'failed' => 0];

        foreach ($this->csvRows($request) as $row) {
            $payload = [
                'name' => trim((string) ($row['name'] ?? '')),
                'sku' => trim((string) ($row['sku'] ?? '')),
                'uom' => trim((string) ($row['uom'] ?? '')),
                'reorder_level' => $row['reorder_level'] ?? 0,
                'is_active' => $this->toBoolean($row['is_active'] ?? true),
                'category' => trim((string) ($row['category'] ?? '')) ?: 'Uncategorized',
            ];

            $validator = Validator::make($payload, [
                'name' => 'required|string|max:255',
                'sku' => 'required|string|max:255',
                'uom' => 'required|string|max:20',
                'reorder_level' => 'nullable|numeric|min:0',
                'is_active' => 'boolean',
                'category' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                $counts['failed']++;
                continue;
            }

            $existing = Item::query()->where('sku', $payload['sku'])->first();
            if ($existing) {
                // Block UoM change if stock already exists for this item.
                if ($existing->uom !== $payload['uom'] && StockTransaction::query()->where('item_id', $existing->id)->exists()) {
                    $payload['uom'] = $existing->uom;
                }

                $existing->update($payload);
                $counts['updated']++;
            } else {
                Item::query()->create($payload);
                $counts['inserted']++;
            }
        }

        return back()->with('success', "Items import done. Inserted: {$counts['inserted']}, Updated: {$counts['updated']}, Failed: {$counts['failed']}");
    }

    public function trail(Item $item)
    {
        $trail = app(\App\Services\InventoryService::class)->procurementToConsumptionTrail($item->id);

        return view('admin.inventory.trail', [
            'item' => $item,
            'inward' => $trail['inward'] ?? [],
            'outward' => $trail['outward'] ?? [],
        ]);
    }

    private function csvRows(Request $request): array
    {
        $rows = [];
        $file = fopen($request->file('file')->getRealPath(), 'r');
        $headers = fgetcsv($file) ?: [];
        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headers);

        while (($line = fgetcsv($file)) !== false) {
            if (!array_filter($line, fn ($v) => trim((string) $v) !== '')) {
                continue;
            }
            $rows[] = array_combine($headers, array_pad($line, count($headers), null));
        }

        fclose($file);

        return $rows;
    }

    private function toBoolean(mixed $value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'y'], true);
    }
}
