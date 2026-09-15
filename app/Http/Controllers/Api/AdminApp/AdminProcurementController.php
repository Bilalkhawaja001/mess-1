<?php

namespace App\Http\Controllers\Api\AdminApp;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\ItemUnit;
use App\Models\KitchenGrn;
use App\Models\KitchenGrnLine;
use App\Models\KitchenPo;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\StockTransaction;
use App\Services\Kitchen\KitchenApprovalService;
use App\Support\DocumentNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminProcurementController extends AdminAuthController
{
    protected const PERM = 'procurement.manage';

    public function pending(Request $request): JsonResponse
    {
        if (! $this->requirePermission($request, self::PERM)) {
            return $this->user($request) ? $this->forbidden() : $this->unauthenticated();
        }

        $pos = KitchenPo::with(['staff:id,name,staff_code', 'vendor:id,name', 'lines.item:id,name,sku,uom'])
            ->where('status', KitchenPo::STATUS_PENDING)
            ->orderBy('id')
            ->get();

        $grns = KitchenGrn::with(['staff:id,name,staff_code', 'kitchenPo:id,temp_number,status', 'lines.item:id,name,sku,uom'])
            ->where('status', KitchenGrn::STATUS_PENDING)
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'purchase_orders' => $pos->map(fn ($po) => [
                'id' => (int) $po->id,
                'temp_number' => $po->temp_number,
                'staff' => $po->staff->name ?? null,
                'vendor' => $po->vendor->name ?? null,
                'po_date' => optional($po->po_date)->format('Y-m-d'),
                'remarks' => $po->remarks,
                'lines' => $po->lines->map(fn ($l) => [
                    'id' => (int) $l->id,
                    'item_id' => (int) $l->item_id,
                    'item' => $l->item->name ?? null,
                    'uom' => $l->item->uom ?? null,
                    'qty_ordered' => (string) $l->qty_ordered,
                ]),
            ]),
            'goods_receipts' => $grns->map(fn ($g) => [
                'id' => (int) $g->id,
                'temp_number' => $g->temp_number,
                'staff' => $g->staff->name ?? null,
                'po_temp_number' => $g->kitchenPo->temp_number ?? null,
                'po_status' => $g->kitchenPo->status ?? null,
                'received_date' => optional($g->received_date)->format('Y-m-d'),
                'lines' => $g->lines->map(fn ($l) => [
                    'line_id' => (int) $l->id,
                    'has_image' => (bool) $l->image_path,
                    'item_id' => (int) $l->item_id,
                    'item' => $l->item->name ?? null,
                    'uom' => $l->item->uom ?? null,
                    'qty_received' => (string) $l->qty_received,
                ]),
            ]),
        ]);
    }

    public function grnLineImage(Request $request, int $lineId)
    {
        if (! $this->requirePermission($request, self::PERM)) {
            return $this->user($request) ? $this->forbidden() : $this->unauthenticated();
        }

        $line = KitchenGrnLine::find($lineId);
        if (! $line) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $path = (string) $line->image_path;
        $disk = (string) ($line->image_disk ?: 'local');

        if ($path === '' || ! in_array($disk, ['local', 'public'], true) || ! Storage::disk($disk)->exists($path)) {
            return response()->json(['success' => false, 'message' => 'Image not available'], 404);
        }

        return response()->file(Storage::disk($disk)->path($path), [
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function approvePo(Request $request, int $id, KitchenApprovalService $service): JsonResponse
    {
        $user = $this->requirePermission($request, self::PERM);
        if (! $user) {
            return $this->user($request) ? $this->forbidden() : $this->unauthenticated();
        }

        $data = $request->validate([
            'rates' => ['nullable', 'array'],
            'rates.*.item_id' => ['required_with:rates', 'integer'],
            'rates.*.unit_price' => ['required_with:rates', 'numeric', 'gte:0'],
        ]);

        try {
            $po = $service->approvePo($id, (int) $user->id, $data['rates'] ?? []);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Purchase order approved',
            'po_number' => $po->po_number,
        ]);
    }

    public function rejectPo(Request $request, int $id, KitchenApprovalService $service): JsonResponse
    {
        $user = $this->requirePermission($request, self::PERM);
        if (! $user) {
            return $this->user($request) ? $this->forbidden() : $this->unauthenticated();
        }

        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        try {
            $service->rejectPo($id, (int) $user->id, $data['reason']);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 422);
        }

        return response()->json(['success' => true, 'message' => 'Purchase order rejected']);
    }

    public function approveGrn(Request $request, int $id, KitchenApprovalService $service): JsonResponse
    {
        $user = $this->requirePermission($request, self::PERM);
        if (! $user) {
            return $this->user($request) ? $this->forbidden() : $this->unauthenticated();
        }

        $data = $request->validate([
            'costs' => ['required', 'array', 'min:1'],
            'costs.*.item_id' => ['required', 'integer'],
            'costs.*.unit_cost' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $grnNumber = $service->approveGrn($id, (int) $user->id, $data['costs']);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'GRN approved and stock posted',
            'grn_number' => $grnNumber,
        ]);
    }

    public function rejectGrn(Request $request, int $id, KitchenApprovalService $service): JsonResponse
    {
        $user = $this->requirePermission($request, self::PERM);
        if (! $user) {
            return $this->user($request) ? $this->forbidden() : $this->unauthenticated();
        }

        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        try {
            $service->rejectGrn($id, (int) $user->id, $data['reason']);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 422);
        }

        return response()->json(['success' => true, 'message' => 'GRN rejected']);
    }
}
