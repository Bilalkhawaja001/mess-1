<?php

namespace App\Http\Controllers\Api\Kitchen;

use App\Models\Item;
use App\Models\KitchenPo;
use App\Models\KitchenPoLine;
use App\Models\Vendor;
use App\Support\DocumentNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KitchenPoController extends KitchenAuthController
{
    public function searchVendors(Request $request): JsonResponse
    {
        $staff = $this->staff($request);
        if (! $staff) {
            return $this->unauthenticated();
        }

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['success' => true, 'vendors' => []]);
        }

        $rows = Vendor::query()
            ->where('is_active', 1)
            ->where(function ($w) use ($q) {
                $w->where('name', 'like', $q.'%')
                    ->orWhere('name', 'like', '%'.$q.'%');
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'phone']);

        return response()->json(['success' => true, 'vendors' => $rows]);
    }

    public function searchItems(Request $request): JsonResponse
    {
        $staff = $this->staff($request);
        if (! $staff) {
            return $this->unauthenticated();
        }

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['success' => true, 'items' => []]);
        }

        $rows = Item::query()
            ->where('is_active', 1)
            ->where(function ($w) use ($q) {
                $w->where('name', 'like', '%'.$q.'%')
                    ->orWhere('sku', 'like', '%'.$q.'%');
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'sku', 'uom']);

        return response()->json(['success' => true, 'items' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $staff = $this->staff($request);
        if (! $staff) {
            return $this->unauthenticated();
        }

        $data = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'po_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty_ordered' => ['required', 'numeric', 'gt:0'],
        ]);

        $po = DB::transaction(function () use ($data, $staff) {
            $po = KitchenPo::create([
                'temp_number' => DocumentNumber::generate('KPO'),
                'kitchen_staff_id' => $staff->id,
                'vendor_id' => (int) $data['vendor_id'],
                'po_date' => $data['po_date'],
                'remarks' => $data['remarks'] ?? null,
                'status' => KitchenPo::STATUS_PENDING,
            ]);

            foreach ($data['lines'] as $line) {
                KitchenPoLine::create([
                    'kitchen_po_id' => $po->id,
                    'item_id' => (int) $line['item_id'],
                    'qty_ordered' => (float) $line['qty_ordered'],
                    'unit_price' => null,
                ]);
            }

            return $po;
        });

        return response()->json([
            'success' => true,
            'message' => 'PO submitted for approval',
            'purchase_order' => $this->poPayload($po->fresh(['lines.item', 'vendor'])),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $staff = $this->staff($request);
        if (! $staff) {
            return $this->unauthenticated();
        }

        $rows = KitchenPo::query()
            ->with(['vendor:id,name'])
            ->withCount('lines')
            ->where('kitchen_staff_id', $staff->id)
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'purchase_orders' => $rows->map(fn ($po) => [
                'id' => (int) $po->id,
                'temp_number' => $po->temp_number,
                'vendor' => $po->vendor->name ?? null,
                'po_date' => optional($po->po_date)->format('Y-m-d'),
                'status' => $po->status,
                'line_count' => (int) $po->lines_count,
                'reject_reason' => $po->reject_reason,
            ]),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $staff = $this->staff($request);
        if (! $staff) {
            return $this->unauthenticated();
        }

        $po = KitchenPo::with(['lines.item:id,name,sku,uom', 'vendor:id,name'])
            ->where('kitchen_staff_id', $staff->id)
            ->find($id);

        if (! $po) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        return response()->json(['success' => true, 'purchase_order' => $this->poPayload($po)]);
    }

    protected function poPayload(KitchenPo $po): array
    {
        return [
            'id' => (int) $po->id,
            'temp_number' => $po->temp_number,
            'vendor' => $po->vendor->name ?? null,
            'po_date' => optional($po->po_date)->format('Y-m-d'),
            'status' => $po->status,
            'remarks' => $po->remarks,
            'reject_reason' => $po->reject_reason,
            'lines' => $po->lines->map(fn ($l) => [
                'item_id' => (int) $l->item_id,
                'item' => $l->item->name ?? null,
                'sku' => $l->item->sku ?? null,
                'uom' => $l->item->uom ?? null,
                'qty_ordered' => (float) $l->qty_ordered,
            ]),
        ];
    }
}
