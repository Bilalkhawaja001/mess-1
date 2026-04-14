<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\StockTransaction;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function index()
    {
        $items = Item::query()->with('units')->orderBy('name')->get();
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
                    ->whereIn('txn_type', ['OUT', 'KITCHEN_ISSUE'])
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
            ->sortBy(fn (array $row) => strtolower((string) $row['item']->name))
            ->values();

        return view('admin.inventory.index', compact('items', 'ledger', 'balances', 'lowStockItems', 'storeStockRows'));
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
