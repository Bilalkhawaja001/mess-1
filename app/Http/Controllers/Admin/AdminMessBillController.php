<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonthlyAttendance;
use App\Support\BusinessMonthCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminMessBillController extends Controller
{
    public function index(Request $request): View
    {
        $monthCycle = trim((string) $request->query('month_cycle', BusinessMonthCycle::defaultDashboardMonthCycle()));
        $cycle = BusinessMonthCycle::resolve($monthCycle);

        $rangeStart = $cycle['cycle_start'];
        $rangeEnd = $cycle['cycle_end'];

        $purchaseTotal = $this->netPurchaseTotal($rangeStart->toDateString(), $rangeEnd->toDateString());

        $attendance = $this->attendanceBuckets($monthCycle);
        $totalAttendance = $attendance['contractors'] + $attendance['executive'] + $attendance['centralized'];

        $perAttendanceExpense = $totalAttendance > 0
            ? round($purchaseTotal / $totalAttendance, 6)
            : 0.0;

        $contractorExpense = round($perAttendanceExpense * $attendance['contractors'], 2);

        $totalExpenses = round($purchaseTotal - $contractorExpense, 2);

        return view('admin.admin_mess_bill.index', [
            'monthCycle' => $monthCycle,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'purchaseTotal' => round($purchaseTotal, 2),
            'totalAttendance' => $totalAttendance,
            'perAttendanceExpense' => $perAttendanceExpense,
            'totalExpenses' => $totalExpenses,
        ]);
    }

    private function netPurchaseTotal(string $fromDate, string $toDate): float
    {
        $returnAgg = DB::table('vendor_returns')
            ->selectRaw('goods_receipt_line_id, SUM(qty_returned) as returned_qty, SUM(qty_returned * unit_cost) as returned_cost')
            ->whereNotNull('goods_receipt_line_id')
            ->groupBy('goods_receipt_line_id');

        $netCostSql = '((goods_receipt_lines.qty_received * goods_receipt_lines.unit_cost) - COALESCE(vr.returned_cost, 0))';

        return round((float) DB::table('goods_receipt_lines')
            ->leftJoinSub($returnAgg, 'vr', function ($join) {
                $join->on('vr.goods_receipt_line_id', '=', 'goods_receipt_lines.id');
            })
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_lines.goods_receipt_id')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'goods_receipts.purchase_order_id')
            ->join('vendors', 'vendors.id', '=', 'purchase_orders.vendor_id')
            ->join('items', 'items.id', '=', 'goods_receipt_lines.item_id')
            ->whereBetween('goods_receipts.received_date', [$fromDate, $toDate])
            ->selectRaw("COALESCE(SUM($netCostSql), 0) as total_cost")
            ->value('total_cost'), 2);
    }

    private function attendanceBuckets(string $monthCycle): array
    {
        $buckets = [
            'contractors' => 0,
            'executive' => 0,
            'centralized' => 0,
        ];

        $rows = MonthlyAttendance::query()
            ->with('member.mess')
            ->where('month_cycle', $monthCycle)
            ->get();

        foreach ($rows as $row) {
            $bucket = $this->normalizeMessBucket((string) ($row->member?->mess?->code ?: $row->member?->mess?->name ?: ''));

            if ($bucket === null) {
                continue;
            }

            $buckets[$bucket] += (int) $row->present_days;
        }

        return $buckets;
    }

    private function normalizeMessBucket(string $messCode): ?string
    {
        $messCode = strtoupper(trim($messCode));

        return match ($messCode) {
            'CONTRACTOR', 'CONTRACTORS' => 'contractors',
            'EXEC', 'EXECUTIVE' => 'executive',
            'CENTRAL', 'CENTRALIZE', 'CENTRALIZED' => 'centralized',
            default => null,
        };
    }
}
