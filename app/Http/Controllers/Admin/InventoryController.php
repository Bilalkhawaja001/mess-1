<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\StockTransaction;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function index()
    {
        $items = Item::query()->get();
        $ledger = StockTransaction::query()->latest('txn_at')->limit(100)->get();
        $balances = $this->inventoryService->stockBalances();

        return view('admin.inventory.index', compact('items', 'ledger', 'balances'));
    }

    public function storeItem(Request $r): RedirectResponse
    {
        $d = $r->validate([
            'name' => 'required',
            'sku' => 'required|unique:items,sku',
            'uom' => 'required',
        ]);

        Item::query()->create($d + ['reorder_level' => $r->input('reorder_level', 0)]);

        return back()->with('success', 'Item created');
    }

    public function storeTxn(Request $r): RedirectResponse
    {
        $d = $r->validate([
            'item_id' => 'required|exists:items,id',
            'txn_type' => 'required|in:OPENING,IN,OUT,ADJUSTMENT',
            'quantity' => 'required|numeric|min:0.001',
            'txn_at' => 'required|date',
        ]);

        StockTransaction::query()->create($d + ['unit_cost' => $r->input('unit_cost', 0), 'remarks' => $r->input('remarks')]);

        return back()->with('success', 'Stock txn posted');
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
            ];

            $validator = Validator::make($payload, [
                'name' => 'required|string|max:255',
                'sku' => 'required|string|max:255',
                'uom' => 'required|string|max:20',
                'reorder_level' => 'nullable|numeric|min:0',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                $counts['failed']++;
                continue;
            }

            $existing = Item::query()->where('sku', $payload['sku'])->first();
            if ($existing) {
                $existing->update($payload);
                $counts['updated']++;
            } else {
                Item::query()->create($payload);
                $counts['inserted']++;
            }
        }

        return back()->with('success', "Items import done. Inserted: {$counts['inserted']}, Updated: {$counts['updated']}, Failed: {$counts['failed']}");
    }

    private function csvRows(Request $request): array
    {
        $rows = [];
        $file = fopen($request->file('file')->getRealPath(), 'r');
        $headers = fgetcsv($file) ?: [];
        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headers);

        while (($line = fgetcsv($file)) !== false) {
            if (! array_filter($line, fn ($v) => trim((string) $v) !== '')) {
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
