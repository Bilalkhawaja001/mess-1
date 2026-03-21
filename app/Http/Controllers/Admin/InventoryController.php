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
        $items = Item::query()->orderBy('name')->get();
        $ledger = StockTransaction::query()->latest('txn_at')->limit(100)->get();
        $balances = $this->inventoryService->stockBalances();

        return view('admin.inventory.index', compact('items', 'ledger', 'balances'));
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
            Item::query()->upsert(
                $rows,
                ['sku'],
                ['name', 'category', 'uom', 'is_active', 'updated_at']
            );
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
        ]);

        StockTransaction::query()->create($data + [
            'unit_cost' => $request->input('unit_cost', 0),
            'remarks' => $request->input('remarks'),
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
