<?php

namespace App\Http\Controllers\Api\Kitchen;

use App\Models\KitchenGrn;
use App\Models\KitchenGrnLine;
use App\Models\KitchenPo;
use App\Support\DocumentNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KitchenGrnController extends KitchenAuthController
{
    public function eligiblePos(Request $request): JsonResponse
    {
        $staff = $this->staff($request);
        if (! $staff) {
            return $this->unauthenticated();
        }

        $rows = KitchenPo::query()
            ->with('vendor:id,name')
            ->where('kitchen_staff_id', $staff->id)
            ->whereIn('status', [KitchenPo::STATUS_PENDING, KitchenPo::STATUS_APPROVED])
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'purchase_orders' => $rows->map(fn ($po) => [
                'id' => (int) $po->id,
                'temp_number' => $po->temp_number,
                'vendor' => $po->vendor->name ?? null,
                'po_date' => optional($po->po_date)->format('Y-m-d'),
                'status' => $po->status,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $staff = $this->staff($request);
        if (! $staff) {
            return $this->unauthenticated();
        }

        $data = $request->validate([
            'kitchen_po_id' => ['required', 'integer'],
            'received_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.item_id' => ['required', 'integer'],
            'lines.*.qty_received' => ['required', 'numeric', 'gt:0'],
        ]);

        $po = KitchenPo::with('lines')
            ->where('kitchen_staff_id', $staff->id)
            ->whereIn('status', [KitchenPo::STATUS_PENDING, KitchenPo::STATUS_APPROVED])
            ->find($data['kitchen_po_id']);

        if (! $po) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        $allowed = $po->lines->pluck('item_id')->map(fn ($v) => (int) $v)->all();
        foreach ($data['lines'] as $line) {
            if (! in_array((int) $line['item_id'], $allowed, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item not present on the selected purchase order',
                ], 422);
            }
        }

        $grn = DB::transaction(function () use ($data, $staff, $po) {
            $grn = KitchenGrn::create([
                'temp_number' => DocumentNumber::generate('KGRN'),
                'kitchen_staff_id' => $staff->id,
                'kitchen_po_id' => $po->id,
                'purchase_order_id' => $po->purchase_order_id,
                'received_date' => $data['received_date'],
                'remarks' => $data['remarks'] ?? null,
                'status' => KitchenGrn::STATUS_PENDING,
            ]);

            foreach ($data['lines'] as $line) {
                KitchenGrnLine::create([
                    'kitchen_grn_id' => $grn->id,
                    'item_id' => (int) $line['item_id'],
                    'qty_received' => (float) $line['qty_received'],
                    'unit_cost' => null,
                ]);
            }

            return $grn;
        });

        return response()->json([
            'success' => true,
            'message' => 'GRN submitted for approval',
            'goods_receipt' => $this->grnPayload($grn->fresh(['lines.item', 'kitchenPo.vendor'])),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $staff = $this->staff($request);
        if (! $staff) {
            return $this->unauthenticated();
        }

        $rows = KitchenGrn::query()
            ->with('kitchenPo:id,temp_number')
            ->withCount('lines')
            ->where('kitchen_staff_id', $staff->id)
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'goods_receipts' => $rows->map(fn ($g) => [
                'id' => (int) $g->id,
                'temp_number' => $g->temp_number,
                'po_temp_number' => $g->kitchenPo->temp_number ?? null,
                'received_date' => optional($g->received_date)->format('Y-m-d'),
                'status' => $g->status,
                'line_count' => (int) $g->lines_count,
                'reject_reason' => $g->reject_reason,
            ]),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $staff = $this->staff($request);
        if (! $staff) {
            return $this->unauthenticated();
        }

        $grn = KitchenGrn::with(['lines.item:id,name,sku,uom', 'kitchenPo.vendor'])
            ->where('kitchen_staff_id', $staff->id)
            ->find($id);

        if (! $grn) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        return response()->json(['success' => true, 'goods_receipt' => $this->grnPayload($grn)]);
    }

    protected function grnPayload(KitchenGrn $grn): array
    {
        return [
            'id' => (int) $grn->id,
            'temp_number' => $grn->temp_number,
            'po_temp_number' => $grn->kitchenPo->temp_number ?? null,
            'vendor' => $grn->kitchenPo->vendor->name ?? null,
            'received_date' => optional($grn->received_date)->format('Y-m-d'),
            'status' => $grn->status,
            'remarks' => $grn->remarks,
            'reject_reason' => $grn->reject_reason,
            'lines' => $grn->lines->map(fn ($l) => [
                'item_id' => (int) $l->item_id,
                'item' => $l->item->name ?? null,
                'sku' => $l->item->sku ?? null,
                'uom' => $l->item->uom ?? null,
                'qty_received' => (float) $l->qty_received,
            ]),
        ];
    }
}
