<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonthlyAttendance;
use App\Models\RatePolicy;
use App\Support\BusinessMonthCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminMessBillController extends Controller
{
    use \App\Support\Pdf\SimplePdfBuilder;

    public function index(Request $request): View
    {
        $monthCycle = trim((string) $request->query('month_cycle', BusinessMonthCycle::defaultDashboardMonthCycle()));

        return view($request->boolean('print') ? 'admin.admin_mess_bill.print' : 'admin.admin_mess_bill.index',
            $this->buildBill($monthCycle));
    }

    public function history(): View
    {
        $months = [];
        $base = \Carbon\Carbon::createFromFormat('!Y-m', BusinessMonthCycle::defaultDashboardMonthCycle());

        for ($i = 0; $i < 12; $i++) {
            $mc = $base->copy()->subMonthsNoOverflow($i)->format('Y-m');

            try {
                $months[] = $this->buildBill($mc);
            } catch (\Throwable $e) {
                continue;
            }
        }

        return view('admin.admin_mess_bill.history', ['months' => $months]);
    }

    public function downloadPdf(Request $request, string $monthCycle): \Illuminate\Http\Response
    {
        $bill = $this->buildBill($monthCycle);

        $pdf = $this->buildMessBillPdf($bill);

        $filename = 'MESS-BILL-'.preg_replace('/[^A-Za-z0-9_-]+/', '_', $monthCycle).'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => strlen($pdf),
        ]);
    }

    private function buildBill(string $monthCycle): array
    {
        $cycle = BusinessMonthCycle::resolve($monthCycle);

        $rangeStart = $cycle['cycle_start'];
        $rangeEnd = $cycle['cycle_end'];
        $from = $rangeStart->toDateString();
        $to = $rangeEnd->toDateString();

        $totalExpenses = $this->netPurchaseTotal($from, $to);

        $guest = DB::table('guest_meals')
            ->whereNotNull('approved_at')
            ->whereDate('meal_date', '>=', $from)
            ->whereDate('meal_date', '<=', $to)
            ->selectRaw('COALESCE(SUM(quantity),0) as qty, COALESCE(SUM(amount),0) as amount')
            ->first();

        $guestQty = (int) ($guest->qty ?? 0);
        $guestAmount = round((float) ($guest->amount ?? 0), 2);
        $guestRate = $guestQty > 0 ? round($guestAmount / $guestQty, 2) : $this->rateFor('GUEST', $from);

        $balanceAmount = round($totalExpenses - $guestAmount, 2);

        $attendance = $this->attendanceBuckets($monthCycle);

        $workerDays = (int) $attendance['contractors'];
        $contractorRate = $this->contractorPerDayRate($from);
        $workerAmount = round($contractorRate * $workerDays, 2);

        $centralizedDays = (int) $attendance['centralized'];
        $centralizedRate = $this->rateFor('RATE_PER_DAY_CENTRALIZED', $from);
        $centralizedAmount = round($centralizedRate * $centralizedDays, 2);

        $executiveDays = (int) $attendance['executive'];
        $executiveRate = $this->rateFor('RATE_PER_DAY_EXECUTIVE', $from);
        $executiveAmount = round($executiveRate * $executiveDays, 2);

        $netBalance = round($balanceAmount - $workerAmount, 2);
        $companyPaid = round($netBalance * 0.50, 2);
        $totalAmount = round($companyPaid + $guestAmount + $workerAmount, 2);

        return [
            'monthCycle' => $monthCycle,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'totalExpenses' => round($totalExpenses, 2),
            'guestQty' => $guestQty,
            'guestRate' => $guestRate,
            'guestAmount' => $guestAmount,
            'balanceAmount' => $balanceAmount,
            'centralizedDays' => $centralizedDays,
            'centralizedRate' => $centralizedRate,
            'centralizedAmount' => $centralizedAmount,
            'executiveDays' => $executiveDays,
            'executiveRate' => $executiveRate,
            'executiveAmount' => $executiveAmount,
            'workerDays' => $workerDays,
            'contractorRate' => $contractorRate,
            'workerAmount' => $workerAmount,
            'netBalance' => $netBalance,
            'companyPaid' => $companyPaid,
            'totalAmount' => $totalAmount,
        ];
    }

    private function buildMessBillPdf(array $b): string
    {
        $money = fn ($v) => number_format((float) $v, 2);
        $paren = fn ($v) => '('.number_format((float) $v, 2).')';

        $W = 595;
        $L = 60;
        $R = 535;

        $stream = '';

        $stream .= $this->pdfText($L, 800, 'Admin Mess Bill Statement - Cycle '.$b['monthCycle'], 7.5);
        $stream .= $this->pdfRightText($R, 800, 'Page 1 of 1', 7.5);

        $stream .= $this->pdfCenterText($W, 762, 'ADMIN MESS', 17, true);
        $stream .= $this->pdfCenterText($W, 742, 'Bill Statement', 10.5);

        $stream .= $this->pdfLine($L, 726, $R, 726, 1.2);

        $stream .= $this->pdfText($L, 712, 'MONTH CYCLE:', 7.5, true);
        $stream .= $this->pdfText($L + 68, 712, $b['monthCycle'], 7.5);

        $stream .= $this->pdfText($L + 130, 712, 'BILLING PERIOD:', 7.5, true);
        $stream .= $this->pdfText($L + 208, 712, $b['rangeStart']->format('d M Y').' - '.$b['rangeEnd']->format('d M Y'), 7.5);

        $stream .= $this->pdfText($L + 330, 712, 'PRINTED ON:', 7.5, true);
        $stream .= $this->pdfText($L + 388, 712, now()->format('d M Y H:i'), 7.5);

        $stream .= $this->pdfLine($L, 702, $R, 702, 1.2);

        $stream .= $this->pdfText($L, 684, 'DESCRIPTION', 7.5);
        $stream .= $this->pdfRightText($R, 684, 'AMOUNT (PKR)', 7.5);
        $stream .= $this->pdfLine($L, 676, $R, 676, 0.9);

        $y = 650;

        $stream .= $this->pdfText($L, $y, 'WORKING', 9.5, true);
        $y -= 22;

        $rows = [
            ['indent', 'Total Expenses', $money($b['totalExpenses']), false],
            ['indent', 'Less: Guest Amount', $paren($b['guestAmount']), false],
            ['bold',   'Balance Amount', $money($b['balanceAmount']), true],
            ['indent', 'Less: Guest Meals ('.$b['guestQty'].' x '.$money($b['guestRate']).')', $paren($b['guestAmount']), false],
            ['indent', 'Centralized Mess ('.$b['centralizedDays'].' days x '.$money($b['centralizedRate']).')', $money($b['centralizedAmount']), false],
            ['indent', 'Executive Mess ('.$b['executiveDays'].' days x '.$money($b['executiveRate']).')', $money($b['executiveAmount']), false],
            ['indent', 'Less: Worker/Employees ('.$b['workerDays'].' days x '.$money($b['contractorRate']).')', $paren($b['workerAmount']), false],
            ['bold',   'Net Balance', $money($b['netBalance']), true],
        ];

        foreach ($rows as [$kind, $label, $value, $bold]) {
            $x = $kind === 'indent' ? $L + 14 : $L;
            $stream .= $this->pdfText($x, $y, $label, 8.5, $bold);
            $stream .= $this->pdfRightText($R, $y, $value, 8.5, $bold);

            $stream .= $bold
                ? $this->pdfLine($L, $y - 8, $R, $y - 8, 0.9)
                : "0.80 0.80 0.80 RG 0.4 w ".$L." ".($y - 8)." m ".$R." ".($y - 8)." l S\n";

            $y -= 24;
        }

        $y -= 10;
        $stream .= $this->pdfText($L, $y, 'PAYABLE', 9.5, true);
        $y -= 22;

        $payable = [
            ['Company Share (50% of Net Balance)', $money($b['companyPaid'])],
            ['Add: Guest Amount', $money($b['guestAmount'])],
            ['Add: LPTL & Wind Power employees & guest Amount', $money($b['workerAmount'])],
        ];

        foreach ($payable as [$label, $value]) {
            $stream .= $this->pdfText($L + 14, $y, $label, 8.5);
            $stream .= $this->pdfRightText($R, $y, $value, 8.5);
            $stream .= "0.80 0.80 0.80 RG 0.4 w ".$L." ".($y - 8)." m ".$R." ".($y - 8)." l S\n";
            $y -= 24;
        }

        $y -= 4;
        $stream .= $this->pdfLine($L, $y + 14, $R, $y + 14, 1.2);
        $stream .= $this->pdfText($L, $y, 'TOTAL AMOUNT TO BE PAID', 10, true);
        $stream .= $this->pdfRightText($R, $y, $money($b['totalAmount']), 10, true);
        $stream .= $this->pdfLine($L, $y - 10, $R, $y - 10, 1.2);

        return $this->assemblePdf([$stream], false, null, 0, 0, 595, 842);
    }

    private function rateFor(string $rateType, string $cycleDate): float
    {
        $value = RatePolicy::query()
            ->where('rate_type', $rateType)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $cycleDate)
            ->whereDate('effective_to', '>=', $cycleDate)
            ->orderByDesc('effective_from')
            ->value('value');

        return round((float) $value, 4);
    }

    private function contractorPerDayRate(string $cycleDate): float
    {
        $value = RatePolicy::query()
            ->where('rate_type', 'RATE_PER_DAY_CONTRACTORS')
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $cycleDate)
            ->whereDate('effective_to', '>=', $cycleDate)
            ->orderByDesc('effective_from')
            ->value('value');

        return round((float) $value, 4);
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

    private function approvedGuestAmount(string $fromDate, string $toDate): float
    {
        return round((float) DB::table('guest_meals')
            ->whereNotNull('approved_at')
            ->whereDate('meal_date', '>=', $fromDate)
            ->whereDate('meal_date', '<=', $toDate)
            ->sum('amount'), 2);
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
