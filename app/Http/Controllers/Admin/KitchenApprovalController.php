<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KitchenGrn;
use App\Models\KitchenGrnLine;
use App\Models\KitchenPo;
use App\Services\Kitchen\KitchenApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class KitchenApprovalController extends Controller
{
    public function index(): View
    {
        $pendingPos = KitchenPo::with(['staff:id,name,staff_code', 'vendor:id,name', 'lines.item:id,name,sku,uom'])
            ->where('status', KitchenPo::STATUS_PENDING)
            ->orderBy('id')
            ->get();

        $pendingGrns = KitchenGrn::with(['staff:id,name,staff_code', 'kitchenPo:id,temp_number,status', 'lines.item:id,name,sku,uom'])
            ->where('status', KitchenGrn::STATUS_PENDING)
            ->orderBy('id')
            ->get();

        $history = KitchenPo::with(['staff:id,name', 'vendor:id,name'])
            ->whereIn('status', [KitchenPo::STATUS_APPROVED, KitchenPo::STATUS_REJECTED])
            ->orderByDesc('reviewed_at')
            ->limit(20)
            ->get();

        return view('admin.kitchen-approvals.index', compact('pendingPos', 'pendingGrns', 'history'));
    }

    public function lineImage(int $lineId)
    {
        $line = KitchenGrnLine::find($lineId);

        if (! $line) {
            abort(404);
        }

        $path = (string) $line->image_path;
        $disk = (string) ($line->image_disk ?: 'local');

        if ($path === '' || ! in_array($disk, ['local', 'public'], true) || ! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        return response()->file(Storage::disk($disk)->path($path), [
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function approvePo(Request $request, int $id, KitchenApprovalService $service): RedirectResponse
    {
        $data = $request->validate([
            'rates' => ['nullable', 'array'],
            'rates.*' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $rates = [];
        foreach ($data['rates'] ?? [] as $itemId => $price) {
            if ($price !== null && $price !== '') {
                $rates[] = ['item_id' => (int) $itemId, 'unit_price' => (float) $price];
            }
        }

        try {
            $po = $service->approvePo($id, (int) Auth::id(), $rates);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Purchase order approved: '.$po->po_number);
    }

    public function rejectPo(Request $request, int $id, KitchenApprovalService $service): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:1000']]);

        try {
            $service->rejectPo($id, (int) Auth::id(), $data['reason']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Purchase order rejected.');
    }

    public function approveGrn(Request $request, int $id, KitchenApprovalService $service): RedirectResponse
    {
        $data = $request->validate([
            'costs' => ['required', 'array', 'min:1'],
            'costs.*' => ['required', 'numeric', 'gt:0'],
        ]);

        $costs = [];
        foreach ($data['costs'] as $itemId => $cost) {
            $costs[] = ['item_id' => (int) $itemId, 'unit_cost' => (float) $cost];
        }

        try {
            $grnNumber = $service->approveGrn($id, (int) Auth::id(), $costs);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'GRN approved and stock posted: '.$grnNumber);
    }

    public function rejectGrn(Request $request, int $id, KitchenApprovalService $service): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:1000']]);

        try {
            $service->rejectGrn($id, (int) Auth::id(), $data['reason']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'GRN rejected.');
    }
}
