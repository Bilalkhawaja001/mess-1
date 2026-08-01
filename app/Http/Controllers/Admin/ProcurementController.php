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
use App\Support\DocumentNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

class ProcurementController extends Controller
{
    public function index(Request $request)
    {
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $items = Item::query()
            ->where('is_active', true)
            ->with('units')
            ->orderBy('sku')
            ->get();
        $poStatusFilter = strtoupper(trim((string) $request->input('po_status', '')));
        $allowedPoStatuses = ['DRAFT', 'APPROVED', 'PARTIALLY_RECEIVED', 'RECEIVED', 'CANCELLED'];
        if (! in_array($poStatusFilter, $allowedPoStatuses, true)) {
            $poStatusFilter = '';
        }

        $pos = PurchaseOrder::query()
            ->with(['vendor', 'lines.item.units', 'goodsReceipts.lines.purchaseOrderLine'])
            ->when($poStatusFilter !== '', fn ($q) => $q->where('status', $poStatusFilter))
            ->latest()
            ->limit(50)
            ->get()
            ->map(function (PurchaseOrder $po) {
                $receivedByLine = $po->goodsReceipts
                    ->flatMap->lines
                    ->groupBy('purchase_order_line_id')
                    ->map(fn ($lines) => (float) $lines->sum('qty_received'));

                $totalOrdered = (float) $po->lines->sum('qty_ordered');
                $totalReceived = (float) $po->goodsReceipts->flatMap->lines->sum('qty_received');
                $totalPending = max($totalOrdered - $totalReceived, 0);

                $po->lines->transform(function (PurchaseOrderLine $line) use ($receivedByLine) {
                    $receivedQty = (float) ($receivedByLine[$line->id] ?? 0);
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
            ->filter(fn (PurchaseOrder $po) => in_array($po->status, ['APPROVED', 'PARTIALLY_RECEIVED'], true)
                && $po->lines->contains(fn ($line) => (float) ($line->pending_qty ?? 0) > 0))
            ->values();

        $poImportPreview = session('procurement_po_import_preview');
        $grnImportPreview = session('procurement_grn_import_preview');
        $selectedGrnTemplatePo = $request->integer('template_po_id');
        [$grnFromDate, $grnToDate] = $this->resolveGrnDateRange($request);
        [$reportFromDate, $reportToDate] = $this->resolvePurchaseReportDateRange($request);
        $reportSearch = trim((string) $request->input('q', ''));

        $grns = GoodsReceipt::query()
            ->with(['purchaseOrder.vendor', 'lines.item'])
            ->whereBetween('received_date', [$grnFromDate, $grnToDate])
            ->when($reportSearch !== '', function ($query) use ($reportSearch) {
                $like = '%'.$reportSearch.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('grn_number', 'like', $like)
                        ->orWhereHas('purchaseOrder', function ($po) use ($like) {
                            $po->where('po_number', 'like', $like)
                                ->orWhereHas('vendor', fn ($vendor) => $vendor->where('name', 'like', $like));
                        });
                });
            })
            ->orderByDesc('received_date')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $purchaseReportData = $this->buildPurchaseReportData($reportFromDate, $reportToDate, $reportSearch);

        $editPo = null;
        $editPoId = $request->integer('edit_po');
        if ($editPoId > 0) {
            $editPo = PurchaseOrder::query()
                ->with(['vendor', 'lines.item.units', 'goodsReceipts'])
                ->find($editPoId);

            if ($editPo && $editPo->goodsReceipts->isNotEmpty()) {
                $editPo = null;
            }
        }

        return view('admin.procurement.index', compact(
            'vendors',
            'items',
            'pos',
            'grnEligiblePos',
            'grns',
            'poImportPreview',
            'grnImportPreview',
            'selectedGrnTemplatePo',
            'grnFromDate',
            'grnToDate',
            'reportFromDate',
            'reportToDate',
            'reportSearch',
            'purchaseReportData',
            'editPo',
            'poStatusFilter'
        ));
    }

    public function downloadPoTemplate(): Response
    {
        $rows = [
            ['vendor_name', 'po_date', 'item_sku', 'item_name', 'qty_ordered', 'unit_price', 'remarks'],
            ['Demo Vendor', now()->toDateString(), 'ITEM-001', 'Demo Item', '10', '125.50', 'po_date format must be YYYY-MM-DD'],
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
            $po = PurchaseOrder::query()->with(['vendor', 'lines.item.units', 'goodsReceipts.lines.purchaseOrderLine'])->find($poId);
            if ($po) {
                foreach ($po->lines as $line) {
                    $receivedQty = (float) $po->goodsReceipts->flatMap->lines->where('purchase_order_line_id', $line->id)->sum('qty_received');
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
        $preview = $request->session()->get('procurement_po_import_preview');

        if (! is_array($preview) || empty($preview['po_groups'])) {
            return redirect()->route('admin.procurement.index', ['tab' => 'po'])->with('error', 'No PO import preview data available. Upload and preview again.');
        }

        if (! empty($preview['error_rows'])) {
            return redirect()->route('admin.procurement.index', ['tab' => 'po'])->with('error', 'Fix PO preview errors before creating POs from uploaded lines.');
        }

        $expectedHash = (string) ($preview['file_hash'] ?? '');
        $actualHash = md5(json_encode([$preview['po_groups'], $preview['error_rows'] ?? []]));
        if ($expectedHash === '' || ! hash_equals($expectedHash, $actualHash)) {
            $request->session()->forget('procurement_po_import_preview');

            return redirect()->route('admin.procurement.index', ['tab' => 'po'])->with('error', 'PO preview data failed integrity check. Re-upload the CSV.');
        }

        $groups = collect($preview['po_groups']);

        foreach ($groups as $group) {
            $groupDate = $this->parseStrictImportDate((string) ($group['po_date'] ?? ''));
            if ($groupDate === null || (int) ($group['vendor_id'] ?? 0) <= 0 || empty($group['rows'])) {
                return redirect()->route('admin.procurement.index', ['tab' => 'po'])->with('error', 'PO preview data is incomplete or invalid. Clear preview and re-upload.');
            }
        }

        $createdPoNumbers = [];

        DB::transaction(function () use ($groups, &$createdPoNumbers) {
            foreach ($groups as $group) {
                $po = PurchaseOrder::create([
                    'vendor_id' => (int) $group['vendor_id'],
                    'po_number' => DocumentNumber::generate('PO'),
                    'po_date' => $group['po_date'],
                    'status' => 'DRAFT',
                    'remarks' => collect($group['rows'])->pluck('remarks')->filter()->unique()->implode(' | ') ?: null,
                ]);

                $lineCount = 0;
                foreach ($group['rows'] as $line) {
                    PurchaseOrderLine::create([
                        'purchase_order_id' => $po->id,
                        'item_id' => (int) $line['item_id'],
                        'qty_ordered' => (float) $line['qty_ordered'],
                        'unit_price' => (float) $line['unit_price'],
                    ]);
                    $lineCount++;
                }

                if ($lineCount <= 0) {
                    throw new \RuntimeException('PO group create failed before any PO lines were saved.');
                }

                $createdPoNumbers[] = $po->po_number;
            }
        });

        \Log::info('PO_IMPORT_STORE_RESULT', [
            'group_count' => $groups->count(),
            'created_po_numbers' => $createdPoNumbers,
        ]);

        $request->session()->forget('procurement_po_import_preview');

        return redirect()->route('admin.procurement.index', ['tab' => 'po'])->with('success', count($createdPoNumbers).' PO(s) created: '.implode(', ', $createdPoNumbers));
    }

    public function exportGrnDetail(Request $request): StreamedResponse
    {
        [$fromDate, $toDate] = $this->resolveGrnDateRange($request, true);
        $search = trim((string) $request->input('q', ''));

        $returnAgg = \Illuminate\Support\Facades\DB::table('vendor_returns')
            ->selectRaw('goods_receipt_line_id, SUM(qty_returned) as returned_qty, SUM(qty_returned * unit_cost) as returned_cost')
            ->whereNotNull('goods_receipt_line_id')
            ->groupBy('goods_receipt_line_id');

        $rows = GoodsReceiptLine::query()
            ->selectRaw("
                goods_receipts.received_date,
                goods_receipts.grn_number,
                goods_receipts.remarks as grn_remarks,
                purchase_orders.po_number,
                vendors.name as vendor_name,
                items.sku,
                items.name as item_name,
                items.uom,
                goods_receipt_lines.qty_received as gross_qty,
                COALESCE(vr.returned_qty, 0) as returned_qty,
                (goods_receipt_lines.qty_received - COALESCE(vr.returned_qty, 0)) as net_qty,
                goods_receipt_lines.unit_cost,
                (goods_receipt_lines.qty_received * goods_receipt_lines.unit_cost) as gross_amount,
                COALESCE(vr.returned_cost, 0) as returned_amount,
                ((goods_receipt_lines.qty_received * goods_receipt_lines.unit_cost) - COALESCE(vr.returned_cost, 0)) as net_amount
            ")
            ->leftJoinSub($returnAgg, 'vr', fn ($join) => $join->on('vr.goods_receipt_line_id', '=', 'goods_receipt_lines.id'))
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_lines.goods_receipt_id')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'goods_receipts.purchase_order_id')
            ->join('vendors', 'vendors.id', '=', 'purchase_orders.vendor_id')
            ->join('items', 'items.id', '=', 'goods_receipt_lines.item_id')
            ->whereBetween('goods_receipts.received_date', [$fromDate, $toDate])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';

                $query->where(function ($inner) use ($like) {
                    $inner->where('items.sku', 'like', $like)
                        ->orWhere('items.name', 'like', $like)
                        ->orWhere('items.category', 'like', $like)
                        ->orWhere('vendors.name', 'like', $like);
                });
            })
            ->whereRaw('(goods_receipt_lines.qty_received - COALESCE(vr.returned_qty, 0)) > 0')
            ->orderBy('goods_receipts.received_date')
            ->orderBy('goods_receipts.grn_number')
            ->orderBy('goods_receipt_lines.id');

        $filename = sprintf('grn_detail_%s_to_%s.csv', $fromDate, $toDate);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'GRN No', 'PO No', 'Vendor', 'Item Code / SKU', 'Description / Item Name', 'Gross Qty', 'Returned Qty', 'Net Qty', 'UOM', 'Unit Price', 'Gross Amount', 'Returned Amount', 'Net Amount', 'Remarks']);

            $rows->chunk(500, function ($chunk) use ($handle) {
                foreach ($chunk as $row) {
                    fputcsv($handle, [
                        $row->received_date,
                        $row->grn_number,
                        $row->po_number,
                        $row->vendor_name,
                        $row->sku,
                        $row->item_name,
                        number_format((float) $row->gross_qty, 3, '.', ''),
                        number_format((float) $row->returned_qty, 3, '.', ''),
                        number_format((float) $row->net_qty, 3, '.', ''),
                        $row->uom,
                        number_format((float) $row->unit_cost, 2, '.', ''),
                        number_format((float) $row->gross_amount, 2, '.', ''),
                        number_format((float) $row->returned_amount, 2, '.', ''),
                        number_format((float) $row->net_amount, 2, '.', ''),
                        $row->grn_remarks,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportGrnSummary(Request $request): StreamedResponse
    {
        [$fromDate, $toDate] = $this->resolveGrnDateRange($request, true);

        $returnAgg = \Illuminate\Support\Facades\DB::table('vendor_returns')
            ->selectRaw('goods_receipt_line_id, SUM(qty_returned) as returned_qty, SUM(qty_returned * unit_cost) as returned_cost')
            ->whereNotNull('goods_receipt_line_id')
            ->groupBy('goods_receipt_line_id');

        $netQtySql = '(goods_receipt_lines.qty_received - COALESCE(vr.returned_qty, 0))';
        $netCostSql = '((goods_receipt_lines.qty_received * goods_receipt_lines.unit_cost) - COALESCE(vr.returned_cost, 0))';

        $rows = GoodsReceiptLine::query()
            ->selectRaw("items.id as item_id, items.sku, items.name as item_name, items.uom, SUM($netQtySql) as total_qty_received, SUM($netCostSql) as total_amount, CASE WHEN SUM($netQtySql) > 0 THEN SUM($netCostSql) / SUM($netQtySql) ELSE 0 END as weighted_avg_price")
            ->leftJoinSub($returnAgg, 'vr', fn ($join) => $join->on('vr.goods_receipt_line_id', '=', 'goods_receipt_lines.id'))
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_lines.goods_receipt_id')
            ->join('items', 'items.id', '=', 'goods_receipt_lines.item_id')
            ->whereBetween('goods_receipts.received_date', [$fromDate, $toDate])
            ->groupBy('items.id', 'items.sku', 'items.name', 'items.uom')
            ->havingRaw("SUM($netQtySql) > 0")
            ->orderBy('items.sku');

        $filename = sprintf('grn_item_summary_%s_to_%s.csv', $fromDate, $toDate);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Item Code / SKU', 'Description / Item Name', 'UOM', 'Net Qty Received', 'Weighted Average Unit Price', 'Net Total Amount']);

            $rows->chunk(500, function ($chunk) use ($handle) {
                foreach ($chunk as $row) {
                    fputcsv($handle, [
                        $row->sku,
                        $row->item_name,
                        $row->uom,
                        number_format((float) $row->total_qty_received, 3, '.', ''),
                        number_format((float) $row->weighted_avg_price, 2, '.', ''),
                        number_format((float) $row->total_amount, 2, '.', ''),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportSelectedPurchaseReport(Request $request): StreamedResponse
    {
        $reportType = trim((string) $request->input('report_type', 'summary'));

        if ($reportType === 'detail') {
            return $this->exportGrnDetail($request);
        }

        return $this->exportPurchaseReports($request);
    }

    public function exportPurchaseReports(Request $request): StreamedResponse
    {
        [$fromDate, $toDate] = $this->resolvePurchaseReportDateRange($request, true);
        $search = trim((string) $request->input('q', ''));
        $reportData = $this->buildPurchaseReportData($fromDate, $toDate, $search);
        $filename = sprintf('purchase_reports_%s_to_%s.csv', $fromDate, $toDate);

        return response()->streamDownload(function () use ($reportData, $fromDate, $toDate, $search) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Overall Totals']);
            fputcsv($handle, ['From Date', $fromDate]);
            fputcsv($handle, ['To Date', $toDate]);
            fputcsv($handle, ['Search', $search !== '' ? $search : 'All']);
            fputcsv($handle, ['Total Purchasing Cost', number_format((float) ($reportData['totals']->total_cost ?? 0), 2, '.', '')]);
            fputcsv($handle, ['Total Purchased Qty', number_format((float) ($reportData['totals']->total_qty ?? 0), 3, '.', '')]);
            fputcsv($handle, ['Unique Items Purchased', (int) ($reportData['totals']->unique_items ?? 0)]);
            fputcsv($handle, ['Vendors Used', (int) ($reportData['totals']->vendors_used ?? 0)]);
            fputcsv($handle, []);

            fputcsv($handle, ['Cost by Category']);
            fputcsv($handle, ['Category', 'Total Qty', 'Total Cost', 'Avg Cost']);
            foreach ($reportData['categoryRows'] as $row) {
                fputcsv($handle, [
                    $row->category,
                    number_format((float) $row->total_qty, 3, '.', ''),
                    number_format((float) $row->total_cost, 2, '.', ''),
                    number_format((float) $row->avg_cost, 2, '.', ''),
                ]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Purchasing Cost by Vendor']);
            fputcsv($handle, ['Vendor', 'Total Qty', 'Total Cost', 'GRN Count']);
            foreach ($reportData['vendorRows'] as $row) {
                fputcsv($handle, [
                    $row->vendor_name,
                    number_format((float) $row->total_qty, 3, '.', ''),
                    number_format((float) $row->total_cost, 2, '.', ''),
                    (int) $row->grn_count,
                ]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Item Purchase Summary']);
            fputcsv($handle, ['Item Code', 'Item Name', 'Category', 'UOM', 'Total Qty', 'Total Cost', 'Avg Cost', 'First Date', 'Last Date']);
            foreach ($reportData['itemRows'] as $row) {
                fputcsv($handle, [
                    $row->sku,
                    $row->item_name,
                    $row->category,
                    $row->uom,
                    number_format((float) $row->total_qty, 3, '.', ''),
                    number_format((float) $row->total_cost, 2, '.', ''),
                    number_format((float) $row->avg_cost, 2, '.', ''),
                    $row->first_grn_date,
                    $row->last_grn_date,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function previewGrnImport(Request $request): RedirectResponse
    {
        $request->validate([
            'grn_import_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $rows = $this->readCsvRows($request->file('grn_import_file')->getRealPath());
        $preview = $this->buildGrnImportPreview($rows);
        $request->session()->put('procurement_grn_import_preview', $preview);

        return redirect()->route('admin.procurement.index', ['tab' => 'grn'])
            ->withInput()
            ->with($preview['error_count'] > 0 ? 'warning' : 'success', $preview['error_count'] > 0
                ? 'GRN import preview generated with validation errors.'
                : 'GRN import preview ready. Review and post GRN.');
    }

    public function storeGrnImport(Request $request): RedirectResponse
    {
        $preview = session('procurement_grn_import_preview');

        \Illuminate\Support\Facades\Log::info('procurement.grn_import.store.hit', [
            'has_preview' => is_array($preview),
            'grn_groups_count' => is_array($preview) && isset($preview['grn_groups']) && is_array($preview['grn_groups']) ? count($preview['grn_groups']) : 0,
            'error_rows_count' => is_array($preview) && isset($preview['error_rows']) && is_array($preview['error_rows']) ? count($preview['error_rows']) : 0,
            'file_hash_present' => is_array($preview) && ! empty($preview['file_hash']),
        ]);

        if (! is_array($preview) || empty($preview['grn_groups'])) {
            return redirect()->route('admin.procurement.index', ['tab' => 'grn'])->with('error', 'No GRN import preview data available. Upload and preview again.');
        }

        if (! empty($preview['error_rows'])) {
            return redirect()->route('admin.procurement.index', ['tab' => 'grn'])->with('error', 'Fix GRN preview errors before posting uploaded lines.');
        }

        $expectedHash = (string) ($preview['file_hash'] ?? '');
        $actualHash = md5(json_encode([$preview['grn_groups'], $preview['error_rows'] ?? []]));
        if ($expectedHash === '' || ! hash_equals($expectedHash, $actualHash)) {
            $request->session()->forget('procurement_grn_import_preview');

            return redirect()->route('admin.procurement.index', ['tab' => 'grn'])->with('error', 'GRN preview data failed integrity check. Re-upload the CSV.');
        }

        $groups = collect($preview['grn_groups']);

        foreach ($groups as $group) {
            if ($this->parseStrictImportDate((string) ($group['received_date'] ?? '')) === null || (int) ($group['po_id'] ?? 0) <= 0 || empty($group['rows'])) {
                return redirect()->route('admin.procurement.index', ['tab' => 'grn'])->with('error', 'GRN preview data is incomplete or invalid. Clear preview and re-upload.');
            }
        }

        $createdGrnNumbers = [];

        try {
            DB::transaction(function () use ($groups, &$createdGrnNumbers) {
                foreach ($groups as $group) {
                    $lockedPo = PurchaseOrder::query()->with(['lines.item.units', 'goodsReceipts.lines'])->lockForUpdate()->findOrFail((int) $group['po_id']);

                    if (! in_array($lockedPo->status, ['APPROVED', 'PARTIALLY_RECEIVED'], true)) {
                        throw new \RuntimeException('PO '.$lockedPo->po_number.' status "'.($lockedPo->status ?? 'UNKNOWN').'" is not eligible for GRN.');
                    }

                    $grn = GoodsReceipt::create([
                        'purchase_order_id' => $lockedPo->id,
                        'grn_number' => DocumentNumber::generate('GRN'),
                        'received_date' => $group['received_date'],
                        'remarks' => collect($group['rows'])->pluck('remarks')->filter()->implode(' | ') ?: null,
                    ]);

                    foreach ($group['rows'] as $row) {
                        $poLine = $lockedPo->lines->firstWhere('id', (int) $row['purchase_order_line_id']);
                        if (! $poLine) {
                            throw new \RuntimeException('PO '.$lockedPo->po_number.': preview line no longer matches PO. Re-upload the CSV.');
                        }

                        $alreadyReceived = (float) $lockedPo->goodsReceipts->flatMap->lines->where('purchase_order_line_id', $poLine->id)->sum('qty_received');
                        $pendingNow = max((float) $poLine->qty_ordered - $alreadyReceived, 0);
                        if ((float) $row['qty_received'] > $pendingNow + 0.0001) {
                            throw new \RuntimeException('PO '.$lockedPo->po_number.' item '.$row['item_sku'].': receive qty exceeds current pending ('.$pendingNow.'). Stock may have been received since preview. Re-upload the CSV.');
                        }

                        $grnLine = GoodsReceiptLine::create([
                            'goods_receipt_id' => $grn->id,
                            'purchase_order_line_id' => (int) $row['purchase_order_line_id'],
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
                            'txn_at' => $group['received_date'],
                            'remarks' => trim(implode(' | ', array_filter([
                                'GRN bulk import',
                                $row['remarks'] ?? null,
                            ]))),
                        ]);
                    }

                    $lockedPo->load(['lines', 'goodsReceipts.lines']);
                    $totalOrderedQty = (float) $lockedPo->lines->sum('qty_ordered');
                    $totalReceivedQty = (float) $lockedPo->goodsReceipts->flatMap->lines->sum('qty_received');

                    PurchaseOrder::whereKey($lockedPo->id)->update([
                        'status' => $totalReceivedQty < $totalOrderedQty ? 'PARTIALLY_RECEIVED' : 'RECEIVED',
                    ]);

                    $createdGrnNumbers[] = $grn->grn_number;
                }
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.procurement.index', ['tab' => 'grn'])->with('error', $e->getMessage());
        }

        $request->session()->forget('procurement_grn_import_preview');

        return redirect()->route('admin.procurement.index', ['tab' => 'grn'])->with('success', count($createdGrnNumbers).' GRN(s) posted: '.implode(', ', $createdGrnNumbers));
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
                'po_number' => DocumentNumber::generate('PO'),
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
    public function cancelPurchaseOrder(PurchaseOrder $po): RedirectResponse
    {
        $grnExists = GoodsReceipt::query()
            ->where('purchase_order_id', $po->id)
            ->exists();

        if ($grnExists) {
            return back()->withErrors(['po' => 'PO cannot be cancelled after GRN is created.']);
        }

        if ($po->status === 'CANCELLED') {
            return back()->with('success', 'PO is already cancelled.');
        }

        $po->status = 'CANCELLED';
        $po->save();

        return redirect()->route('admin.procurement.index', ['tab' => 'po'])
            ->with('success', 'PO cancelled successfully.');
    }

    public function updatePurchaseOrderLines(Request $request, PurchaseOrder $po): RedirectResponse
    {
        $grnExists = GoodsReceipt::query()
            ->where('purchase_order_id', $po->id)
            ->exists();

        if ($grnExists) {
            return back()->withErrors(['po' => 'PO cannot be edited after GRN is created.']);
        }

        if ($po->status === 'CANCELLED') {
            return back()->withErrors(['po' => 'Cancelled PO cannot be edited.']);
        }

        $data = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.qty_ordered' => ['nullable', 'numeric', 'min:0.001'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.remove' => ['nullable'],
        ]);

        $po->load('lines');

        $remaining = 0;
        foreach ($po->lines as $line) {
            $row = $data['lines'][$line->id] ?? null;
            if (!$row) {
                $remaining++;
                continue;
            }

            if (!empty($row['remove'])) {
                continue;
            }

            $remaining++;
        }

        if ($remaining <= 0) {
            return back()->withErrors(['po' => 'At least one PO line must remain.']);
        }

        DB::transaction(function () use ($po, $data) {
            $po->load('lines');

            foreach ($po->lines as $line) {
                $row = $data['lines'][$line->id] ?? null;
                if (!$row) {
                    continue;
                }

                if (!empty($row['remove'])) {
                    $line->delete();
                    continue;
                }

                $line->qty_ordered = (float) $row['qty_ordered'];
                $line->unit_price = (float) $row['unit_price'];
                $line->save();
            }
        });

        return redirect()->route('admin.procurement.index', ['tab' => 'po', 'edit_po' => $po->id])
            ->with('success', 'PO lines updated successfully.');
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
        $legacySingleRow = ! $r->has('receive_rows') && $r->filled('purchase_order_line_id') && $r->filled('item_id');

        if ($legacySingleRow) {
            $r->merge([
                'receive_rows' => [[
                    'selected' => true,
                    'purchase_order_line_id' => $r->input('purchase_order_line_id'),
                    'item_id' => $r->input('item_id'),
                    'qty_received' => $r->input('qty_received'),
                    'unit_cost' => $r->input('unit_cost'),
                    'unit_code' => $r->input('unit_code'),
                    'override_po_rate' => $r->boolean('override_po_rate'),
                    'override_reason' => $r->input('override_reason'),
                    'remarks' => $r->input('remarks'),
                ]],
            ]);
        }

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
            return back()->withErrors([$legacySingleRow ? 'qty_received' : 'receive_rows' => 'Select at least one PO row to receive.'])->withInput();
        }

        $po = PurchaseOrder::query()->with(['lines.item.units', 'goodsReceipts.lines'])->findOrFail($d['purchase_order_id']);

        try {
            $validatedRows = $this->buildValidatedGrnRows($po, $selectedRows, ! $legacySingleRow);
        } catch (\RuntimeException $e) {
            return back()->withErrors([$legacySingleRow ? 'qty_received' : 'receive_rows' => $e->getMessage()])->withInput();
        }

        DB::transaction(function () use ($d, $po, $validatedRows, $legacySingleRow, &$grn) {
            $lockedPo = PurchaseOrder::query()->with(['lines.item.units', 'goodsReceipts.lines'])->lockForUpdate()->findOrFail($po->id);
            $lockedRows = $this->buildValidatedGrnRows($lockedPo, $validatedRows->map(fn (array $row) => $row['request'])->values(), ! $legacySingleRow);

            $grn = GoodsReceipt::create([
                'purchase_order_id' => $lockedPo->id,
                'grn_number' => DocumentNumber::generate('GRN'),
                'received_date' => $d['received_date'],
                'remarks' => $lockedRows->pluck('request.remarks')->filter()->implode(' | ') ?: null,
            ]);

            foreach ($lockedRows as $row) {
                $meta = $row['meta'];
                $requestRow = $row['request'];

                $grnLinePayload = [
                    'goods_receipt_id' => $grn->id,
                    'item_id' => $meta['item']->id,
                    'qty_received' => $meta['qty_received'],
                    'unit_cost' => $meta['unit_cost'],
                ];

                if (Schema::hasColumn('goods_receipt_lines', 'purchase_order_line_id')) {
                    $grnLinePayload['purchase_order_line_id'] = $meta['po_line']->id;
                }

                $grnLine = GoodsReceiptLine::create($grnLinePayload);

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

        if ($legacySingleRow) {
            return redirect('/admin/procurement')->with('success', 'GRN posted successfully.');
        }

        return redirect()->route('admin.procurement.index', ['tab' => 'grn'])->with('success', 'GRN posted for selected PO rows.');
    }

    public function reverseGrn(GoodsReceipt $grn, Request $request): RedirectResponse
    {
        $data = $request->validate([
            "reason" => "required|string|min:5|max:500",
        ]);

        $already = StockTransaction::query()
            ->where("txn_type", "GRN_REVERSAL")
            ->where("reference_type", GoodsReceipt::class)
            ->where("reference_id", $grn->id)
            ->exists();
        if ($already) {
            return back()->with("error", "This GRN has already been reversed.");
        }

        try {
            DB::transaction(function () use ($grn, $data) {
                $lockedGrn = GoodsReceipt::query()->with("lines")->lockForUpdate()->findOrFail($grn->id);

                $reAgain = StockTransaction::query()
                    ->where("txn_type", "GRN_REVERSAL")
                    ->where("reference_type", GoodsReceipt::class)
                    ->where("reference_id", $lockedGrn->id)
                    ->lockForUpdate()
                    ->exists();
                if ($reAgain) {
                    throw new \RuntimeException("This GRN has already been reversed.");
                }

                foreach ($lockedGrn->lines as $grnLine) {
                    $orig = StockTransaction::query()
                        ->where("txn_type", "GRN")
                        ->where("reference_type", GoodsReceiptLine::class)
                        ->where("reference_id", $grnLine->id)
                        ->first();
                    if (! $orig) {
                        throw new \RuntimeException("Original stock transaction missing for a GRN line; cannot reverse safely.");
                    }

                    StockTransaction::create([
                        "item_id" => $orig->item_id,
                        "txn_type" => "GRN_REVERSAL",
                        "quantity" => -1 * (float) $orig->quantity,
                        "unit_cost" => $orig->unit_cost,
                        "trans_unit_code" => $orig->trans_unit_code,
                        "trans_quantity" => -1 * (float) $orig->trans_quantity,
                        "reference_type" => GoodsReceipt::class,
                        "reference_id" => $lockedGrn->id,
                        "txn_at" => now(),
                        "remarks" => "GRN reversal (" . $lockedGrn->grn_number . "). Reason: " . $data["reason"],
                    ]);
                }

                $po = PurchaseOrder::query()->with(["lines", "goodsReceipts.lines"])->lockForUpdate()->find($lockedGrn->purchase_order_id);
                if ($po) {
                    $reversedGrnIds = StockTransaction::query()
                        ->where("txn_type", "GRN_REVERSAL")
                        ->where("reference_type", GoodsReceipt::class)
                        ->pluck("reference_id")
                        ->all();

                    $totalOrderedQty = (float) $po->lines->sum("qty_ordered");
                    $totalReceivedQty = (float) $po->goodsReceipts
                        ->reject(fn ($g) => in_array($g->id, $reversedGrnIds, true))
                        ->flatMap->lines->sum("qty_received");

                    $status = $totalReceivedQty <= 0
                        ? "APPROVED"
                        : ($totalReceivedQty < $totalOrderedQty ? "PARTIALLY_RECEIVED" : "RECEIVED");

                    PurchaseOrder::whereKey($po->id)->update(["status" => $status]);
                }
            });
        } catch (\RuntimeException $e) {
            return back()->with("error", $e->getMessage());
        }

        return back()->with("success", "GRN reversed. Stock has been rolled back.");
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
        $groups = [];
        $errorRows = [];
        $totalRows = 0;

        foreach ($rows as $index => $row) {
            $lineNumber = $index + 2;
            $totalRows++;

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

            $strictPoDate = $this->parseStrictImportDate($normalized['po_date']);
            if ($strictPoDate === null) {
                $errors[] = 'po_date must be in strict YYYY-MM-DD format (e.g. 2026-06-13). Formats like 17-06-26 or 24/06/26 are rejected';
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

            if ($errors) {
                $errorRows[] = [
                    'line_number' => $lineNumber,
                    'data' => $normalized,
                    'errors' => $errors,
                ];
                continue;
            }

            $groupKey = $vendor->id.'|'.$strictPoDate;

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'vendor_id' => $vendor->id,
                    'vendor_name' => $vendor->name,
                    'po_date' => $strictPoDate,
                    'rows' => [],
                    'row_count' => 0,
                    'total_qty' => 0.0,
                    'total_value' => 0.0,
                    'seen_item_ids' => [],
                    'errors' => [],
                ];
            }

            if (in_array($item->id, $groups[$groupKey]['seen_item_ids'], true)) {
                $errorRows[] = [
                    'line_number' => $lineNumber,
                    'data' => $normalized,
                    'errors' => ['duplicate item in same PO group (same vendor + same po_date) is not allowed'],
                ];
                continue;
            }

            $groups[$groupKey]['seen_item_ids'][] = $item->id;
            $groups[$groupKey]['rows'][] = [
                'line_number' => $lineNumber,
                'item_id' => $item->id,
                'item_sku' => $item->sku,
                'item_name' => $item->name,
                'qty_ordered' => $qtyOrdered,
                'unit_price' => round($unitPrice, 2),
                'remarks' => $normalized['remarks'],
            ];
            $groups[$groupKey]['row_count']++;
            $groups[$groupKey]['total_qty'] += $qtyOrdered;
            $groups[$groupKey]['total_value'] += $qtyOrdered * round($unitPrice, 2);
        }

        $poGroups = array_values(array_map(function (array $g): array {
            unset($g['seen_item_ids']);
            $g['total_qty'] = round($g['total_qty'], 3);
            $g['total_value'] = round($g['total_value'], 2);

            return $g;
        }, $groups));

        $preview = [
            'po_groups' => $poGroups,
            'error_rows' => $errorRows,
            'total_rows' => $totalRows,
            'vendor_count' => count(array_unique(array_column($poGroups, 'vendor_id'))),
            'group_count' => count($poGroups),
            'total_value' => round(array_sum(array_column($poGroups, 'total_value')), 2),
            'valid_count' => (int) array_sum(array_column($poGroups, 'row_count')),
            'error_count' => count($errorRows),
        ];

        $preview['file_hash'] = md5(json_encode([$preview['po_groups'], $preview['error_rows']]));

        return $preview;
    }

    private function buildGrnImportPreview(array $rows): array
    {
        $groups = [];
        $errorRows = [];
        $totalRows = 0;
        $poCache = [];

        foreach ($rows as $index => $row) {
            $lineNumber = $index + 2;
            $totalRows++;

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

            $strictReceivedDate = $this->parseStrictImportDate($normalized['received_date']);
            if ($strictReceivedDate === null) {
                $errors[] = 'received_date must be in strict YYYY-MM-DD format (e.g. 2026-06-13). Formats like 17-06-26 or 24/06/26 are rejected';
            }

            $poRow = null;
            if ($normalized['po_number'] !== '') {
                if (! array_key_exists($normalized['po_number'], $poCache)) {
                    $poCache[$normalized['po_number']] = PurchaseOrder::query()
                        ->with(['vendor', 'lines.item.units', 'goodsReceipts.lines'])
                        ->where('po_number', $normalized['po_number'])
                        ->first();
                }
                $poRow = $poCache[$normalized['po_number']];
            }
            if (! $poRow) {
                $errors[] = 'po_number does not match existing PO';
            } elseif (! in_array($poRow->status, ['APPROVED', 'PARTIALLY_RECEIVED'], true)) {
                $errors[] = 'PO status "'.($poRow->status ?? 'UNKNOWN').'" is not eligible for GRN. Only APPROVED or PARTIALLY_RECEIVED POs can be received';
            }

            $item = $this->resolveItemForImport($normalized['item_sku'], $normalized['item_name']);
            if (! $item) {
                $errors[] = 'item_sku/item_name could not match an item';
            }

            $qtyReceived = is_numeric($normalized['qty_received']) ? (float) $normalized['qty_received'] : 0;
            if ($qtyReceived <= 0) {
                $errors[] = 'qty_received must be greater than zero';
            }

            $groupKey = ($poRow?->id ?? 0).'|'.($strictReceivedDate ?? '');

            $poLine = null;
            $pendingQty = null;
            $unitCode = $normalized['unit_code'];
            $unitFactor = 1.0;
            $unitCost = is_numeric($normalized['unit_cost']) ? (float) $normalized['unit_cost'] : null;

            if ($poRow && $item && in_array($poRow->status, ['APPROVED', 'PARTIALLY_RECEIVED'], true)) {
                $poLine = $poRow->lines->firstWhere('item_id', $item->id);
                if (! $poLine) {
                    $errors[] = 'item does not belong to selected PO';
                } else {
                    $alreadyReceived = (float) $poRow->goodsReceipts->flatMap->lines->where('purchase_order_line_id', $poLine->id)->sum('qty_received');
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

                    if (isset($groups[$groupKey]) && in_array($poLine->id, $groups[$groupKey]['seen_line_ids'], true)) {
                        $errors[] = 'duplicate PO line in same GRN group (same po_number + same received_date) is not allowed';
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

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'po_id' => $poRow->id,
                    'po_number' => $poRow->po_number,
                    'vendor_name' => $poRow->vendor->name ?? '-',
                    'received_date' => $strictReceivedDate,
                    'rows' => [],
                    'row_count' => 0,
                    'total_qty' => 0.0,
                    'total_value' => 0.0,
                    'seen_line_ids' => [],
                    'errors' => [],
                ];
            }

            $groups[$groupKey]['seen_line_ids'][] = $poLine->id;
            $groups[$groupKey]['rows'][] = [
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
                'remarks' => $normalized['remarks'],
            ];
            $groups[$groupKey]['row_count']++;
            $groups[$groupKey]['total_qty'] += $qtyReceived;
            $groups[$groupKey]['total_value'] += $qtyReceived * round((float) $unitCost, 2);
        }

        $grnGroups = array_values(array_map(function (array $g): array {
            unset($g['seen_line_ids']);
            $g['total_qty'] = round($g['total_qty'], 3);
            $g['total_value'] = round($g['total_value'], 2);

            return $g;
        }, $groups));

        $preview = [
            'grn_groups' => $grnGroups,
            'error_rows' => $errorRows,
            'total_rows' => $totalRows,
            'po_count' => count(array_unique(array_column($grnGroups, 'po_id'))),
            'group_count' => count($grnGroups),
            'total_value' => round(array_sum(array_column($grnGroups, 'total_value')), 2),
            'valid_count' => (int) array_sum(array_column($grnGroups, 'row_count')),
            'error_count' => count($errorRows),
        ];

        $preview['file_hash'] = md5(json_encode([$preview['grn_groups'], $preview['error_rows']]));

        return $preview;
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

    public function cancelPoImportPreview(Request $request): RedirectResponse
    {
        $request->session()->forget('procurement_po_import_preview');

        return redirect()->route('admin.procurement.index', ['tab' => 'po'])->with('success', 'PO import preview cleared.');
    }

    public function cancelGrnImportPreview(Request $request): RedirectResponse
    {
        $request->session()->forget('procurement_grn_import_preview');

        return redirect()->route('admin.procurement.index', ['tab' => 'grn'])->with('success', 'GRN import preview cleared.');
    }

    private function parseStrictImportDate(string $value): ?string
    {
        $value = trim($value);

        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return null;
        }

        if (! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }

        return $value;
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

    private function buildPurchaseReportBaseQuery(string $fromDate, string $toDate, string $search = '')
    {
        $query = GoodsReceiptLine::query()
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_lines.goods_receipt_id')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'goods_receipts.purchase_order_id')
            ->join('vendors', 'vendors.id', '=', 'purchase_orders.vendor_id')
            ->join('items', 'items.id', '=', 'goods_receipt_lines.item_id')
            ->whereBetween('goods_receipts.received_date', [$fromDate, $toDate]);

        if (Schema::hasColumn('goods_receipts', 'status')) {
            $query->whereIn('goods_receipts.status', ['APPROVED', 'POSTED', 'RECEIVED']);
        }

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('items.sku', 'like', $like)
                    ->orWhere('items.name', 'like', $like)
                    ->orWhere('items.category', 'like', $like)
                    ->orWhere('vendors.name', 'like', $like);
            });
        }

        return $query;
    }

    private function buildPurchaseReportData(string $fromDate, string $toDate, string $search = ''): array
    {
        $returnAgg = \Illuminate\Support\Facades\DB::table('vendor_returns')
            ->selectRaw('goods_receipt_line_id, SUM(qty_returned) as returned_qty, SUM(qty_returned * unit_cost) as returned_cost')
            ->whereNotNull('goods_receipt_line_id')
            ->groupBy('goods_receipt_line_id');

        $netBase = function () use ($fromDate, $toDate, $search, $returnAgg) {
            return $this->buildPurchaseReportBaseQuery($fromDate, $toDate, $search)
                ->leftJoinSub($returnAgg, 'vr', function ($join) {
                    $join->on('vr.goods_receipt_line_id', '=', 'goods_receipt_lines.id');
                });
        };

        $netQtySql = '(goods_receipt_lines.qty_received - COALESCE(vr.returned_qty, 0))';
        $netCostSql = '((goods_receipt_lines.qty_received * goods_receipt_lines.unit_cost) - COALESCE(vr.returned_cost, 0))';

        $baseTotals = $netBase()
            ->selectRaw("COALESCE(SUM($netCostSql), 0) as total_cost, COALESCE(SUM($netQtySql), 0) as total_qty, COUNT(DISTINCT items.id) as unique_items, COUNT(DISTINCT vendors.id) as vendors_used")
            ->first();

        $categoryRows = $netBase()
            ->selectRaw("COALESCE(items.category, 'Uncategorized') as category, SUM($netQtySql) as total_qty, SUM($netCostSql) as total_cost, CASE WHEN SUM($netQtySql) > 0 THEN SUM($netCostSql) / SUM($netQtySql) ELSE 0 END as avg_cost")
            ->groupBy('items.category')
            ->orderBy('category')
            ->get();

        $vendorRows = $netBase()
            ->selectRaw("vendors.name as vendor_name, SUM($netQtySql) as total_qty, SUM($netCostSql) as total_cost, COUNT(DISTINCT goods_receipts.id) as grn_count")
            ->groupBy('vendors.id', 'vendors.name')
            ->orderBy('vendors.name')
            ->get();

        $itemRows = $netBase()
            ->selectRaw("items.id as item_id, items.sku, items.name as item_name, COALESCE(items.category, 'Uncategorized') as category, items.uom, SUM($netQtySql) as total_qty, SUM($netCostSql) as total_cost, CASE WHEN SUM($netQtySql) > 0 THEN SUM($netCostSql) / SUM($netQtySql) ELSE 0 END as avg_cost, MIN(goods_receipts.received_date) as first_grn_date, MAX(goods_receipts.received_date) as last_grn_date")
            ->groupBy('items.id', 'items.sku', 'items.name', 'items.category', 'items.uom')
            ->orderBy('items.sku')
            ->get();

        $dateRows = $netBase()
            ->selectRaw("goods_receipts.received_date, SUM($netCostSql) as total_cost, SUM($netQtySql) as total_qty, COUNT(DISTINCT goods_receipts.id) as grn_count, COUNT(DISTINCT purchase_orders.id) as po_count")
            ->groupBy('goods_receipts.received_date')
            ->orderByDesc('goods_receipts.received_date')
            ->get();

        $grnDetails = $netBase()
            ->whereRaw("$netQtySql > 0")
            ->selectRaw("
                goods_receipts.received_date,
                goods_receipts.id as grn_id,
                goods_receipts.grn_number,
                purchase_orders.id as po_id,
                purchase_orders.po_number,
                vendors.name as vendor_name,
                items.sku as item_code,
                items.name as item_name,
                COALESCE(items.category, 'Uncategorized') as category,
                items.uom,
                goods_receipt_lines.qty_received,
                COALESCE(vr.returned_qty, 0) as returned_qty,
                $netQtySql as net_qty,
                goods_receipt_lines.unit_cost,
                (goods_receipt_lines.qty_received * goods_receipt_lines.unit_cost) as gross_cost,
                COALESCE(vr.returned_cost, 0) as returned_cost,
                $netCostSql as total_cost
            ")
            ->orderByDesc('goods_receipts.received_date')
            ->orderByDesc('goods_receipts.id')
            ->orderBy('items.name')
            ->limit(300)
            ->get();

        return [
            'totals' => $baseTotals,
            'categoryRows' => $categoryRows,
            'vendorRows' => $vendorRows,
            'itemRows' => $itemRows,
            'grnDetails' => $grnDetails,
            'dateRows' => $dateRows,
        ];
    }

    private function resolvePurchaseReportDateRange(Request $request, bool $validate = false): array
    {
        $defaultFrom = DB::table('goods_receipts')->min('received_date') ?: now()->startOfMonth()->toDateString();
        $defaultTo = DB::table('goods_receipts')->max('received_date') ?: now()->endOfMonth()->toDateString();

        $data = [
            'from_date' => $request->input('from_date', $defaultFrom),
            'to_date' => $request->input('to_date', $defaultTo),
        ];

        if ($validate) {
            validator($data, [
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
            ])->validate();
        }

        $fromDate = date('Y-m-d', strtotime((string) $data['from_date']));
        $toDate = date('Y-m-d', strtotime((string) $data['to_date']));

        return [$fromDate, $toDate];
    }

    private function resolveGrnDateRange(Request $request, bool $validate = false): array
    {
        $defaultFrom = DB::table('goods_receipts')->min('received_date') ?: now()->startOfMonth()->toDateString();
        $defaultTo = DB::table('goods_receipts')->max('received_date') ?: now()->endOfMonth()->toDateString();

        $data = [
            'from_date' => $request->input('from_date', $defaultFrom),
            'to_date' => $request->input('to_date', $defaultTo),
        ];

        if ($validate) {
            validator($data, [
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
            ])->validate();
        }

        $fromDate = date('Y-m-d', strtotime((string) $data['from_date']));
        $toDate = date('Y-m-d', strtotime((string) $data['to_date']));

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

    private function approvePurchaseOrderRecord(PurchaseOrder $po): void
    {
        if (! in_array($po->status, ['DRAFT', 'ISSUED'], true)) {
            return;
        }

        $po->status = 'APPROVED';
        $po->save();
    }

    private function buildValidatedGrnRows(PurchaseOrder $po, $selectedRows, bool $enforcePoRateOverride = true): Collection
    {
        if (! in_array($po->status, ['APPROVED', 'PARTIALLY_RECEIVED'], true)) {
            throw new \RuntimeException('PO status "'.($po->status ?? 'UNKNOWN').'" is not eligible for GRN. Only APPROVED or PARTIALLY_RECEIVED POs can be received.');
        }

        $selectedRows = collect($selectedRows)->values();

        if ($selectedRows->isEmpty()) {
            throw new \RuntimeException('Select at least one PO row to receive.');
        }

        $poHasPendingLines = $po->lines->contains(function (PurchaseOrderLine $line) use ($po): bool {
            $receivedQty = $this->receivedQtyForPoLine($po, $line);

            return max((float) $line->qty_ordered - $receivedQty, 0) > 0;
        });

        if (! $poHasPendingLines) {
            throw new \RuntimeException('Selected PO is already fully received.');
        }

        return $selectedRows->map(function (array $row) use ($po, $enforcePoRateOverride) {
            $poLine = $po->lines->firstWhere('id', (int) ($row['purchase_order_line_id'] ?? 0));
            if (! $poLine) {
                throw new \RuntimeException('Selected PO line is invalid.');
            }

            if ((int) $poLine->item_id !== (int) ($row['item_id'] ?? 0)) {
                throw new \RuntimeException('Selected item does not match the PO item.');
            }

            $lineReceivedQty = $this->receivedQtyForPoLine($po, $poLine);
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

            if ($enforcePoRateOverride) {
                if (! $overridePoRate && $unitCost !== $poUnitPrice) {
                    throw new \RuntimeException('GRN unit cost must match the selected PO line rate unless override is enabled.');
                }

                if ($overridePoRate && blank($row['override_reason'] ?? null)) {
                    throw new \RuntimeException('Override reason is required when PO rate override is enabled.');
                }
            } else {
                $overridePoRate = $unitCost !== $poUnitPrice;
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

    private function receivedQtyForPoLine(PurchaseOrder $po, PurchaseOrderLine $poLine): float
    {
        $lines = $po->goodsReceipts->flatMap->lines;
        $linkedQty = (float) $lines
            ->where('purchase_order_line_id', (int) $poLine->id)
            ->sum('qty_received');

        if ($linkedQty > 0) {
            return $linkedQty;
        }

        return (float) $lines
            ->whereNull('purchase_order_line_id')
            ->where('item_id', (int) $poLine->item_id)
            ->sum('qty_received');
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
