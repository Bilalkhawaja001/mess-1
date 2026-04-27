<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\StockTransaction;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

class ProcurementController extends Controller
{
    public function index(Request $request)
    {
        $vendors = Vendor::all();
        $items = Item::query()
            ->where('is_active', true)
            ->with('units')
            ->orderBy('sku')
            ->get();
        $pos = PurchaseOrder::query()
            ->with(['vendor', 'lines.item.units', 'goodsReceipts.lines'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(function (PurchaseOrder $po) {
                $receivedByItem = $po->goodsReceipts
                    ->flatMap->lines
                    ->groupBy('item_id')
                    ->map(fn ($lines) => (float) $lines->sum('qty_received'));

                $totalOrdered = (float) $po->lines->sum('qty_ordered');
                $totalReceived = (float) $po->goodsReceipts->flatMap->lines->sum('qty_received');
                $totalPending = max($totalOrdered - $totalReceived, 0);

                $po->lines->transform(function (PurchaseOrderLine $line) use ($receivedByItem) {
                    $receivedQty = (float) ($receivedByItem[$line->item_id] ?? 0);
                    $pendingQty = max((float) $line->qty_ordered - $receivedQty, 0);

                    $line->setAttribute('received_qty', $receivedQty);
                    $line->setAttribute('pending_qty', $pendingQty);

                    return $line;
                });

                $po->setAttribute('total_lines', $po->lines->count());
                $po->setAttribute('total_qty', $totalOrdered);
                $po->setAttribute('total_amount', (float) $po->lines->sum(fn ($line) => ((float) $line->qty_ordered) * ((float) $line->unit_price)));
                $po->setAttribute('received_qty', $totalReceived);
                $po->setAttribute('pending_qty', $totalPending);

                return $po;
            });
        $grnEligiblePos = $pos
            ->filter(fn (PurchaseOrder $po) => $po->lines->contains(fn ($line) => (float) ($line->pending_qty ?? 0) > 0))
            ->values();
        $grns = GoodsReceipt::query()->with(['purchaseOrder.vendor', 'lines.item'])->latest()->limit(50)->get();

        $poImportPreview = session('procurement_po_import_preview');
        $grnImportPreview = session('procurement_grn_import_preview');
        $selectedGrnTemplatePo = $request->integer('template_po_id');

        return view('admin.procurement.index', compact(
            'vendors',
            'items',
            'pos',
            'grnEligiblePos',
            'grns',
            'poImportPreview',
            'grnImportPreview',
            'selectedGrnTemplatePo'
        ));
    }

    public function downloadPoTemplate(): Response
    {
        $rows = [
            ['vendor_name', 'po_date', 'item_sku', 'item_name', 'qty_ordered', 'unit_price', 'remarks'],
            ['Demo Vendor', now()->toDateString(), 'ITEM-001', 'Demo Item', '10', '125.50', 'optional remarks'],
        ];

        return $this->csvDownloadResponse('po_template.csv', $rows);
    }

    public function downloadGrnTemplate(Request $request): Response
    {
        $rows = [
            ['po_number', 'received_date', 'item_sku', 'item_name', 'pending_qty', 'qty_received', 'unit_cost', 'unit_code', 'remarks'],
        ];

        $poId = (int) $request->input('purchase_order_id');
        if ($poId > 0) {
            $po = PurchaseOrder::query()->with(['vendor', 'lines.item.units', 'goodsReceipts.lines'])->find($poId);
            if ($po) {
                foreach ($po->lines as $line) {
                    $receivedQty = (float) $po->goodsReceipts->flatMap->lines->where('item_id', $line->item_id)->sum('qty_received');
                    $pendingQty = max((float) $line->qty_ordered - $receivedQty, 0);
                    if ($pendingQty <= 0) {
                        continue;
                    }

                    $defaultUnit = $line->item?->units?->firstWhere('is_default_for_grn', true)
                        ?? $line->item?->units?->firstWhere('factor_to_base', 1.0)
                        ?? $line->item?->units?->first();

                    $rows[] = [
                        $po->po_number,
                        now()->toDateString(),
                        $line->item?->sku,
                        $line->item?->name,
                        number_format($pendingQty, 3, '.', ''),
                        number_format($pendingQty, 3, '.', ''),
                        number_format((float) $line->unit_price, 2, '.', ''),
                        $defaultUnit?->unit_code ?? $line->item?->uom,
                        '',
                    ];
                }
            }
        }

        if (count($rows) === 1) {
            $rows[] = ['PO-0001', now()->toDateString(), 'ITEM-001', 'Demo Item', '5.000', '5.000', '125.50', 'kg', 'optional remarks'];
        }

        return $this->csvDownloadResponse('grn_template.csv', $rows);
    }

    public function previewPoImport(Request $request): RedirectResponse
    {
        $request->validate([
            'po_import_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $rows = $this->readCsvRows($request->file('po_import_file')->getRealPath());
        $preview = $this->buildPoImportPreview($rows);

        $request->session()->put('procurement_po_import_preview', $preview);

        return redirect()->route('admin.procurement.index', ['tab' => 'po'])
            ->withInput()
            ->with($preview['error_count'] > 0 ? 'warning' : 'success', $preview['error_count'] > 0
                ? 'PO import preview generated with validation errors.'
                : 'PO import preview ready. Review and create PO.');
    }

    public function storePoImport(Request $request): RedirectResponse
    {
        \Log::info('PO_IMPORT_STORE_HIT', [
            'time' => now()->toDateTimeString(),
            'method' => $request->method(),
            'path' => $request->path(),
            'all_keys' => array_keys($request->all()),
            'session_keys' => array_keys($request->session()->all()),
        ]);

        $preview = $request->session()->get('procurement_po_import_preview');
        if (! is_array($preview) || empty($preview['valid_rows'])) {
            return redirect()->route('admin.procurement.index', ['tab' => 'po'])->with('error', 'No PO import preview data available. Upload and preview again.');
        }

        if (! empty($preview['error_rows'])) {
            return redirect()->route('admin.procurement.index', ['tab' => 'po'])->with('error', 'Fix PO preview errors before creating PO from uploaded lines.');
        }

        $vendorId = (int) ($preview['vendor_id'] ?? 0);
        $poDate = (string) ($preview['po_date'] ?? '');
        $remarks = collect($preview['valid_rows'])->pluck('remarks')->filter()->unique()->implode(' | ');
        $validRows = collect($preview['valid_rows']);

        if ($vendorId <= 0 || $poDate === '' || $validRows->isEmpty()) {
            return redirect()->route('admin.procurement.index', ['tab' => 'po'])->with('error', 'PO preview data is incomplete.');
        }

        $po = null;
        $createdLineCount = 0;

        DB::transaction(function () use ($vendorId, $poDate, $remarks, $validRows, &$po, &$createdLineCount) {
            $po = PurchaseOrder::create([
                'vendor_id' => $vendorId,
                'po_number' => 'PO-'.now()->format('YmdHis'),
                'po_date' => $poDate,
                'status' => 'DRAFT',
                'remarks' => $remarks !== '' ? $remarks : null,
            ]);

            foreach ($validRows as $line) {
                PurchaseOrderLine::create([
                    'purchase_order_id' => $po->id,
                    'item_id' => (int) $line['item_id'],
                    'qty_ordered' => (float) $line['qty_ordered'],
                    'unit_price' => (float) $line['unit_price'],
                ]);
                $createdLineCount++;
            }

            if (! $po || $createdLineCount <= 0) {
                throw new \RuntimeException('PO import create failed before any PO lines were saved.');
            }
        });

        \Log::info('PO_IMPORT_STORE_RESULT', [
            'valid_rows_count' => $validRows->count(),
            'po_id' => $po->id ?? null,
            'line_count' => isset($po) ? $po->lines()->count() : null,
        ]);

        $request->session()->forget('procurement_po_import_preview');

        return redirect()->route('admin.procurement.index', ['tab' => 'po'])->with('success', 'PO created from uploaded lines.');
    }

    public function previewGrnImport(Request $request): RedirectResponse
    {
        $request->validate([
            'grn_import_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $rows = $this->readCsvRows($request->file('grn_import_file')->getRealPath());
        $preview = $this->buildGrnImportPreview($rows);

        return redirect()->route('admin.procurement.index', ['tab' => 'grn'])
            ->withInput()
            ->with('procurement_grn_import_preview', $preview)
            ->with($preview['error_count'] > 0 ? 'warning' : 'success', $preview['error_count'] > 0
                ? 'GRN import preview generated with validation errors.'
                : 'GRN import preview ready. Review and post GRN.');
    }

    public function storeGrnImport(Request $request): RedirectResponse
    {
        $preview = session('procurement_grn_import_preview');
        if (! is_array($preview) || empty($preview['valid_rows'])) {
            return redirect()->route('admin.procurement.index', ['tab' => 'grn'])->with('error', 'No GRN import preview data available. Upload and preview again.');
        }

        if (! empty($preview['error_rows'])) {
            return redirect()->route('admin.procurement.index', ['tab' => 'grn'])->with('error', 'Fix GRN preview errors before posting uploaded lines.');
        }

        $poId = (int) ($preview['purchase_order_id'] ?? 0);
        $receivedDate = (string) ($preview['received_date'] ?? '');
        $validRows = collect($preview['valid_rows']);

        if ($poId <= 0 || $receivedDate === '' || $validRows->isEmpty()) {
            return redirect()->route('admin.procurement.index', ['tab' => 'grn'])->with('error', 'GRN preview data is incomplete.');
        }

        DB::transaction(function () use ($poId, $receivedDate, $validRows, &$grn) {
            $po = PurchaseOrder::query()->with(['lines.item.units', 'goodsReceipts.lines'])->lockForUpdate()->findOrFail($poId);

            $grn = GoodsReceipt::create([
                'purchase_order_id' => $po->id,
                'grn_number' => 'GRN-'.now()->format('YmdHis'),
                'received_date' => $receivedDate,
                'remarks' => $validRows->pluck('remarks')->filter()->implode(' | ') ?: null,
            ]);

            foreach ($validRows as $row) {
                $grnLine = GoodsReceiptLine::create([
                    'goods_receipt_id' => $grn->id,
                    'item_id' => (int) $row['item_id'],
                    'qty_received' => (float) $row['qty_received'],
                    'unit_cost' => (float) $row['unit_cost'],
                ]);

                $unitFactor = (float) ($row['unit_factor'] ?? 1);
                StockTransaction::create([
                    'item_id' => (int) $row['item_id'],
                    'txn_type' => 'GRN',
                    'quantity' => (float) $row['qty_received'] * $unitFactor,
                    'unit_cost' => (float) $row['unit_cost'],
                    'trans_unit_code' => $row['unit_code'],
                    'trans_quantity' => (float) $row['qty_received'],
                    'reference_type' => GoodsReceiptLine::class,
                    'reference_id' => $grnLine->id,
                    'txn_at' => $receivedDate,
                    'remarks' => trim(implode(' | ', array_filter([
                        'GRN bulk import',
                        $row['remarks'] ?? null,
                    ]))),
                ]);
            }

            $po->load(['lines', 'goodsReceipts.lines']);
            $totalOrderedQty = (float) $po->lines->sum('qty_ordered');
            $totalReceivedQty = (float) $po->goodsReceipts->flatMap->lines->sum('qty_received');

            PurchaseOrder::whereKey($po->id)->update([
                'status' => $totalReceivedQty < $totalOrderedQty ? 'PARTIALLY_RECEIVED' : 'RECEIVED',
            ]);
        });

        $request->session()->forget('procurement_grn_import_preview');

        return redirect()->route('admin.procurement.index', ['tab' => 'grn'])->with('success', 'GRN posted from uploaded lines.');
    }

    public function storeVendor(Request $r): RedirectResponse
    {
        Vendor::create($r->validate(['name' => 'required']));

        return redirect()->route('admin.procurement.index', ['tab' => 'vendors'])->with('success', 'Vendor created');
    }

    public function storePo(Request $r): RedirectResponse
    {
        $d = $r->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'po_date' => 'required|date',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,id',
            'lines.*.qty_ordered' => 'required|numeric|gt:0',
            'lines.*.unit_price' => 'required|numeric|gt:0',
        ]);

        $linePayloads = collect($d['lines'])
            ->map(function (array $line) {
                return [
                    'item_id' => (int) $line['item_id'],
                    'qty_ordered' => (float) $line['qty_ordered'],
                    'unit_price' => (float) ($line['unit_price'] ?? 0),
                ];
            })
            ->filter(fn (array $line) => $line['item_id'] > 0 && $line['qty_ordered'] > 0)
            ->values();

        if ($linePayloads->isEmpty()) {
            return back()->withErrors(['lines' => 'At least one valid PO line is required.'])->withInput();
        }

        if ($linePayloads->pluck('item_id')->duplicates()->isNotEmpty()) {
            return back()->withErrors(['lines' => 'Same item cannot be added twice in the same PO.'])->withInput();
        }

        $po = null;

        DB::transaction(function () use ($d, $r, $linePayloads, &$po) {
            $po = PurchaseOrder::create([
                'vendor_id' => $d['vendor_id'],
                'po_number' => 'PO-'.now()->format('YmdHis'),
                'po_date' => $d['po_date'],
                'status' => 'DRAFT',
                'remarks' => $r->input('remarks'),
            ]);

            foreach ($linePayloads as $line) {
                PurchaseOrderLine::create([
                    'purchase_order_id' => $po->id,
                    'item_id' => $line['item_id'],
                    'qty_ordered' => $line['qty_ordered'],
                    'unit_price' => $line['unit_price'],
                ]);
            }
        });

        return redirect()->route('admin.procurement.index', ['tab' => 'po'])->with('success', 'PO created');
    }

    public function approvePo(PurchaseOrder $po): RedirectResponse
    {
        $this->approvePurchaseOrderRecord($po);

        return back()->with('success', 'PO approved. Current schema has no deeper approval posting beyond status transition.');
    }

    public function bulkApprovePo(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'po_ids' => 'required|array|min:1',
            'po_ids.*' => 'integer|exists:purchase_orders,id',
        ]);

        $pos = PurchaseOrder::query()->whereIn('id', $data['po_ids'])->get();

        foreach ($pos as $po) {
            $this->approvePurchaseOrderRecord($po);
        }

        return back()->with('success', $pos->count().' purchase order(s) approved.');
    }

    public function storeGrn(Request $r): RedirectResponse
    {
        $d = $r->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'received_date' => 'required|date',
            'receive_rows' => 'required|array|min:1',
            'receive_rows.*' => 'nullable|array',
            'receive_rows.*.selected' => 'nullable|boolean',
            'receive_rows.*.purchase_order_line_id' => 'required_with:receive_rows.*.selected|exists:purchase_order_lines,id',
            'receive_rows.*.item_id' => 'required_with:receive_rows.*.selected|exists:items,id',
            'receive_rows.*.qty_received' => 'nullable|numeric|gt:0',
            'receive_rows.*.unit_cost' => 'nullable|numeric|gt:0',
            'receive_rows.*.unit_code' => 'nullable|string|max:20',
            'receive_rows.*.override_po_rate' => 'nullable|boolean',
            'receive_rows.*.override_reason' => 'nullable|string|max:500',
            'receive_rows.*.remarks' => 'nullable|string|max:1000',
        ]);

        $selectedRows = collect($d['receive_rows'] ?? [])->filter(fn (array $row) => (bool) ($row['selected'] ?? false))->values();

        if ($selectedRows->isEmpty()) {
            return back()->withErrors(['receive_rows' => 'Select at least one PO row to receive.'])->withInput();
        }

        $po = PurchaseOrder::query()->with(['lines.item.units', 'goodsReceipts.lines'])->findOrFail($d['purchase_order_id']);

        try {
            $validatedRows = $this->buildValidatedGrnRows($po, $selectedRows);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['receive_rows' => $e->getMessage()])->withInput();
        }

        DB::transaction(function () use ($d, $po, $validatedRows, &$grn) {
            $lockedPo = PurchaseOrder::query()->with(['lines.item.units', 'goodsReceipts.lines'])->lockForUpdate()->findOrFail($po->id);
            $lockedRows = $this->buildValidatedGrnRows($lockedPo, $validatedRows->map(fn (array $row) => $row['request'])->values());

            $grn = GoodsReceipt::create([
                'purchase_order_id' => $lockedPo->id,
                'grn_number' => 'GRN-'.now()->format('YmdHis'),
                'received_date' => $d['received_date'],
                'remarks' => $lockedRows->pluck('request.remarks')->filter()->implode(' | ') ?: null,
            ]);

            foreach ($lockedRows as $row) {
                $meta = $row['meta'];
                $requestRow = $row['request'];

                $grnLine = GoodsReceiptLine::create([
                    'goods_receipt_id' => $grn->id,
                    'item_id' => $meta['item']->id,
                    'qty_received' => $meta['qty_received'],
                    'unit_cost' => $meta['unit_cost'],
                ]);

                $baseQty = $meta['qty_received'] * (float) $meta['unit']->factor_to_base;

                StockTransaction::create([
                    'item_id' => $meta['item']->id,
                    'txn_type' => 'GRN',
                    'quantity' => $baseQty,
                    'unit_cost' => $meta['unit_cost'],
                    'trans_unit_code' => $meta['unit']->unit_code,
                    'trans_quantity' => $meta['qty_received'],
                    'reference_type' => GoodsReceiptLine::class,
                    'reference_id' => $grnLine->id,
                    'txn_at' => $d['received_date'],
                    'remarks' => trim(implode(' | ', array_filter([
                        'GRN posting (stock posted on create)',
                        $requestRow['remarks'] ?? null,
                        $meta['override_note'] ?? null,
                    ]))),
                ]);
            }

            $lockedPo->load(['lines', 'goodsReceipts.lines']);
            $totalOrderedQty = (float) $lockedPo->lines->sum('qty_ordered');
            $totalReceivedQty = (float) $lockedPo->goodsReceipts->flatMap->lines->sum('qty_received');

            PurchaseOrder::whereKey($lockedPo->id)->update([
                'status' => $totalReceivedQty < $totalOrderedQty ? 'PARTIALLY_RECEIVED' : 'RECEIVED',
            ]);
        });

        return redirect()->route('admin.procurement.index', ['tab' => 'grn'])->with('success', 'GRN posted for selected PO rows.');
    }

    public function approveGrn(GoodsReceipt $grn): RedirectResponse
    {
        $this->acknowledgeGoodsReceiptRecord($grn);

        return back()->with('success', 'GRN approval acknowledged. Stock was already posted on GRN create; no extra approval side-effect exists in current schema.');
    }

    public function bulkApproveGrn(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'grn_ids' => 'required|array|min:1',
            'grn_ids.*' => 'integer|exists:goods_receipts,id',
        ]);

        $grns = GoodsReceipt::query()->whereIn('id', $data['grn_ids'])->get();

        foreach ($grns as $grn) {
            $this->acknowledgeGoodsReceiptRecord($grn);
        }

        return back()->with('success', $grns->count().' GRN acknowledgement(s) processed.');
    }

    private function buildPoImportPreview(array $rows): array
    {
        $validRows = [];
        $errorRows = [];
        $vendorName = null;
        $poDate = null;
        $vendorId = null;
        $seenItemIds = [];

        foreach ($rows as $index => $row) {
            $lineNumber = $index + 2;
            $normalized = [
                'vendor_name' => trim((string) ($row['vendor_name'] ?? '')),
                'po_date' => trim((string) ($row['po_date'] ?? '')),
                'item_sku' => trim((string) ($row['item_sku'] ?? '')),
                'item_name' => trim((string) ($row['item_name'] ?? '')),
                'qty_ordered' => trim((string) ($row['qty_ordered'] ?? '')),
                'unit_price' => trim((string) ($row['unit_price'] ?? '')),
                'remarks' => trim((string) ($row['remarks'] ?? '')),
            ];

            $errors = [];
            if ($normalized['vendor_name'] === '') {
                $errors[] = 'vendor_name is required';
            }
            if ($normalized['po_date'] === '' || ! strtotime($normalized['po_date'])) {
                $errors[] = 'po_date must be a valid date';
            }

            $vendor = $normalized['vendor_name'] !== ''
                ? Vendor::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($normalized['vendor_name'])])->first()
                : null;
            if (! $vendor) {
                $errors[] = 'vendor_name does not match existing vendor';
            }

            $item = $this->resolveItemForImport($normalized['item_sku'], $normalized['item_name']);
            if (! $item) {
                $errors[] = 'item_sku/item_name could not match an item';
            }

            $qtyOrdered = is_numeric($normalized['qty_ordered']) ? (float) $normalized['qty_ordered'] : 0;
            if ($qtyOrdered <= 0) {
                $errors[] = 'qty_ordered must be greater than zero';
            }

            $unitPrice = is_numeric($normalized['unit_price']) ? (float) $normalized['unit_price'] : 0;
            if ($unitPrice <= 0) {
                $errors[] = 'unit_price must be greater than zero';
            }

            if ($vendor) {
                if ($vendorName === null) {
                    $vendorName = $vendor->name;
                    $vendorId = $vendor->id;
                } elseif ($vendorName !== $vendor->name) {
                    $errors[] = 'all rows must belong to the same vendor_name';
                }
            }

            if ($normalized['po_date'] !== '' && strtotime($normalized['po_date'])) {
                $normalizedDate = date('Y-m-d', strtotime($normalized['po_date']));
                if ($poDate === null) {
                    $poDate = $normalizedDate;
                } elseif ($poDate !== $normalizedDate) {
                    $errors[] = 'all rows must have the same po_date';
                }
            }

            if ($item && in_array($item->id, $seenItemIds, true)) {
                $errors[] = 'duplicate item in same PO upload is not allowed';
            }

            if ($errors) {
                $errorRows[] = [
                    'line_number' => $lineNumber,
                    'data' => $normalized,
                    'errors' => $errors,
                ];
                continue;
            }

            $seenItemIds[] = $item->id;
            $validRows[] = [
                'line_number' => $lineNumber,
                'vendor_name' => $vendor->name,
                'vendor_id' => $vendor->id,
                'po_date' => $poDate,
                'item_id' => $item->id,
                'item_sku' => $item->sku,
                'item_name' => $item->name,
                'qty_ordered' => $qtyOrdered,
                'unit_price' => round($unitPrice, 2),
                'remarks' => $normalized['remarks'],
            ];
        }

        return [
            'vendor_id' => $vendorId,
            'vendor_name' => $vendorName,
            'po_date' => $poDate,
            'valid_rows' => $validRows,
            'error_rows' => $errorRows,
            'valid_count' => count($validRows),
            'error_count' => count($errorRows),
        ];
    }

    private function buildGrnImportPreview(array $rows): array
    {
        $validRows = [];
        $errorRows = [];
        $po = null;
        $receivedDate = null;
        $seenLineIds = [];

        foreach ($rows as $index => $row) {
            $lineNumber = $index + 2;
            $normalized = [
                'po_number' => trim((string) ($row['po_number'] ?? '')),
                'received_date' => trim((string) ($row['received_date'] ?? '')),
                'item_sku' => trim((string) ($row['item_sku'] ?? '')),
                'item_name' => trim((string) ($row['item_name'] ?? '')),
                'pending_qty' => trim((string) ($row['pending_qty'] ?? '')),
                'qty_received' => trim((string) ($row['qty_received'] ?? '')),
                'unit_cost' => trim((string) ($row['unit_cost'] ?? '')),
                'unit_code' => trim((string) ($row['unit_code'] ?? '')),
                'remarks' => trim((string) ($row['remarks'] ?? '')),
            ];

            $errors = [];
            if ($normalized['po_number'] === '') {
                $errors[] = 'po_number is required';
            }
            if ($normalized['received_date'] === '' || ! strtotime($normalized['received_date'])) {
                $errors[] = 'received_date must be a valid date';
            }

            $poRow = $normalized['po_number'] !== ''
                ? PurchaseOrder::query()->with(['lines.item.units', 'goodsReceipts.lines'])->where('po_number', $normalized['po_number'])->first()
                : null;
            if (! $poRow) {
                $errors[] = 'po_number does not match existing PO';
            }

            if ($poRow) {
                if ($po === null) {
                    $po = $poRow;
                } elseif ($po->id !== $poRow->id) {
                    $errors[] = 'all rows must belong to the same po_number';
                }
            }

            $item = $this->resolveItemForImport($normalized['item_sku'], $normalized['item_name']);
            if (! $item) {
                $errors[] = 'item_sku/item_name could not match an item';
            }

            $qtyReceived = is_numeric($normalized['qty_received']) ? (float) $normalized['qty_received'] : 0;
            if ($qtyReceived <= 0) {
                $errors[] = 'qty_received must be greater than zero';
            }

            $normalizedDate = null;
            if ($normalized['received_date'] !== '' && strtotime($normalized['received_date'])) {
                $normalizedDate = date('Y-m-d', strtotime($normalized['received_date']));
                if ($receivedDate === null) {
                    $receivedDate = $normalizedDate;
                } elseif ($receivedDate !== $normalizedDate) {
                    $errors[] = 'all rows must have the same received_date';
                }
            }

            $poLine = null;
            $pendingQty = null;
            $unitCode = $normalized['unit_code'];
            $unitFactor = 1.0;
            $unitCost = is_numeric($normalized['unit_cost']) ? (float) $normalized['unit_cost'] : null;

            if ($poRow && $item) {
                $poLine = $poRow->lines->firstWhere('item_id', $item->id);
                if (! $poLine) {
                    $errors[] = 'item does not belong to selected PO';
                } else {
                    $alreadyReceived = (float) $poRow->goodsReceipts->flatMap->lines->where('item_id', $item->id)->sum('qty_received');
                    $pendingQty = max((float) $poLine->qty_ordered - $alreadyReceived, 0);
                    if ($pendingQty <= 0) {
                        $errors[] = 'selected PO line is already fully received';
                    }
                    if ($qtyReceived > $pendingQty) {
                        $errors[] = 'qty_received cannot exceed pending quantity';
                    }

                    $providedPending = is_numeric($normalized['pending_qty']) ? (float) $normalized['pending_qty'] : null;
                    if ($providedPending !== null && abs($providedPending - $pendingQty) > 0.0001) {
                        $errors[] = 'pending_qty does not match actual PO pending quantity';
                    }

                    $unit = null;
                    if ($unitCode !== '') {
                        $unit = $item->units->firstWhere('unit_code', $unitCode);
                        if (! $unit) {
                            $errors[] = 'unit_code does not match item unit';
                        }
                    } else {
                        $unit = $item->units->firstWhere('is_default_for_grn', true)
                            ?? $item->units->firstWhere('factor_to_base', 1.0)
                            ?? $item->units->first();
                        $unitCode = $unit?->unit_code ?? $item->uom;
                    }

                    if ($unit) {
                        $unitFactor = (float) $unit->factor_to_base;
                    }

                    if ($unitCost === null || $unitCost <= 0) {
                        $unitCost = (float) $poLine->unit_price;
                    }

                    if ($poLine && in_array($poLine->id, $seenLineIds, true)) {
                        $errors[] = 'duplicate PO line in same GRN upload is not allowed';
                    }
                }
            }

            if ($unitCost !== null && $unitCost <= 0) {
                $errors[] = 'unit_cost must be greater than zero';
            }

            if ($errors) {
                $errorRows[] = [
                    'line_number' => $lineNumber,
                    'data' => $normalized,
                    'errors' => $errors,
                ];
                continue;
            }

            $seenLineIds[] = $poLine->id;
            $validRows[] = [
                'line_number' => $lineNumber,
                'purchase_order_id' => $poRow->id,
                'po_number' => $poRow->po_number,
                'purchase_order_line_id' => $poLine->id,
                'item_id' => $item->id,
                'item_sku' => $item->sku,
                'item_name' => $item->name,
                'pending_qty' => $pendingQty,
                'qty_received' => $qtyReceived,
                'unit_cost' => round((float) $unitCost, 2),
                'unit_code' => $unitCode,
                'unit_factor' => $unitFactor,
                'received_date' => $receivedDate,
                'remarks' => $normalized['remarks'],
            ];
        }

        return [
            'purchase_order_id' => $po?->id,
            'po_number' => $po?->po_number,
            'received_date' => $receivedDate,
            'valid_rows' => $validRows,
            'error_rows' => $errorRows,
            'valid_count' => count($validRows),
            'error_count' => count($errorRows),
        ];
    }

    private function resolveItemForImport(string $sku, string $name): ?Item
    {
        if ($sku !== '') {
            $item = Item::query()->with('units')->whereRaw('LOWER(sku) = ?', [mb_strtolower($sku)])->first();
            if ($item) {
                return $item;
            }
        }

        if ($name !== '') {
            return Item::query()->with('units')->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        }

        return null;
    }

    private function readCsvRows(string $path): array
    {
        $reader = new Csv();
        $reader->setReadDataOnly(true);
        $reader->setDelimiter(',');
        $reader->setEnclosure('"');
        $reader->setSheetIndex(0);

        $spreadsheet = $reader->load($path);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        $header = array_map(fn ($value) => trim((string) $value), array_shift($rows) ?? []);
        $header = array_values(array_filter($header, fn ($value) => $value !== ''));

        if (empty($header)) {
            throw new \RuntimeException('CSV header row is missing.');
        }

        $mapped = [];
        foreach ($rows as $row) {
            if (collect($row)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty()) {
                continue;
            }

            $mapped[] = collect($header)->mapWithKeys(function ($column, $index) use ($row) {
                return [$column => $row[$index] ?? null];
            })->all();
        }

        return $mapped;
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

    private function approvePurchaseOrderRecord(PurchaseOrder $po): void
    {
        $po->status = 'APPROVED';
        $po->save();
    }

    private function buildValidatedGrnRows(PurchaseOrder $po, $selectedRows): Collection
    {
        $selectedRows = collect($selectedRows)->values();

        if ($selectedRows->isEmpty()) {
            throw new \RuntimeException('Select at least one PO row to receive.');
        }

        $poHasPendingLines = $po->lines->contains(function (PurchaseOrderLine $line) use ($po): bool {
            $receivedQty = (float) $po->goodsReceipts
                ->flatMap->lines
                ->where('item_id', (int) $line->item_id)
                ->sum('qty_received');

            return max((float) $line->qty_ordered - $receivedQty, 0) > 0;
        });

        if (! $poHasPendingLines) {
            throw new \RuntimeException('Selected PO is already fully received.');
        }

        return $selectedRows->map(function (array $row) use ($po) {
            $poLine = $po->lines->firstWhere('id', (int) ($row['purchase_order_line_id'] ?? 0));
            if (! $poLine) {
                throw new \RuntimeException('Selected PO line is invalid.');
            }

            if ((int) $poLine->item_id !== (int) ($row['item_id'] ?? 0)) {
                throw new \RuntimeException('Selected item does not match the PO item.');
            }

            $lineReceivedQty = (float) $po->goodsReceipts
                ->flatMap->lines
                ->where('item_id', (int) $poLine->item_id)
                ->sum('qty_received');
            $orderedQty = (float) $poLine->qty_ordered;
            $pendingQty = max($orderedQty - $lineReceivedQty, 0);

            if ($pendingQty <= 0) {
                throw new \RuntimeException('One selected PO row is already fully received.');
            }

            $qtyReceived = (float) ($row['qty_received'] ?? 0);
            if ($qtyReceived <= 0) {
                throw new \RuntimeException('Received quantity must be greater than zero for each selected row.');
            }

            if ($qtyReceived > $pendingQty) {
                throw new \RuntimeException('Receive quantity cannot exceed pending quantity.');
            }

            $item = Item::query()->with('units')->findOrFail((int) $row['item_id']);
            $unitCode = trim((string) ($row['unit_code'] ?? ''));
            $unit = $item->units->firstWhere('unit_code', $unitCode);
            if (! $unit) {
                throw new \RuntimeException('Invalid unit for selected item.');
            }

            $poUnitPrice = round((float) $poLine->unit_price, 2);
            $unitCost = round((float) ($row['unit_cost'] ?? 0), 2);
            $overridePoRate = (bool) ($row['override_po_rate'] ?? false);

            if ($unitCost <= 0) {
                throw new \RuntimeException('Unit cost must be greater than zero for each selected row.');
            }

            if (! $overridePoRate && $unitCost !== $poUnitPrice) {
                throw new \RuntimeException('GRN unit cost must match the selected PO line rate unless override is enabled.');
            }

            if ($overridePoRate && blank($row['override_reason'] ?? null)) {
                throw new \RuntimeException('Override reason is required when PO rate override is enabled.');
            }

            $overrideNote = $overridePoRate
                ? 'GRN rate override. PO rate: '.number_format($poUnitPrice, 2, '.', '').'; Actual GRN rate: '.number_format($unitCost, 2, '.', '').'; Reason: '.trim((string) ($row['override_reason'] ?? ''))
                : null;

            return [
                'request' => $row,
                'meta' => [
                    'po_line' => $poLine,
                    'item' => $item,
                    'unit' => $unit,
                    'qty_received' => $qtyReceived,
                    'unit_cost' => $unitCost,
                    'pending_qty' => $pendingQty,
                    'override_note' => $overrideNote,
                ],
            ];
        });
    }

    private function acknowledgeGoodsReceiptRecord(GoodsReceipt $grn): void
    {
        $po = PurchaseOrder::query()->with(['lines', 'goodsReceipts.lines'])->findOrFail($grn->purchase_order_id);
        $totalOrderedQty = (float) $po->lines->sum('qty_ordered');
        $totalReceivedQty = (float) $po->goodsReceipts->flatMap->lines->sum('qty_received');

        PurchaseOrder::whereKey($grn->purchase_order_id)->update([
            'status' => $totalReceivedQty < $totalOrderedQty ? 'PARTIALLY_RECEIVED' : 'RECEIVED',
        ]);
        $grn->touch();
    }
}
