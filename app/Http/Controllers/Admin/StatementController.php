<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\Member;
use App\Models\MemberLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatementController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $memberId = trim((string) $request->input('member_id', ''));
        $memberQuery = trim((string) $request->input('member_q', ''));
        $monthCycle = trim((string) $request->input('month_cycle', ''));
        $fromMonth = trim((string) $request->input('from_month', ''));
        $toMonth = trim((string) $request->input('to_month', ''));
        $fromDate = trim((string) $request->input('from_date', ''));
        $toDate = trim((string) $request->input('to_date', ''));
        $export = trim((string) $request->input('export', ''));

        $members = Member::query()
            ->where('is_active', true)
            ->when($memberQuery !== '', function ($query) use ($memberQuery) {
                $query->where(function ($inner) use ($memberQuery) {
                    $inner->where('member_code', 'like', "%{$memberQuery}%")
                        ->orWhere('name', 'like', "%{$memberQuery}%")
                        ->orWhere('department_name', 'like', "%{$memberQuery}%");
                });
            })
            ->orderBy('member_code')
            ->get();

        $query = MemberLedger::query()
            ->with('member')
            ->whereIn('ref_type', ['BILL', 'PAYMENT', 'ADJUSTMENT']);

        if ($memberId !== '') {
            $query->where('member_id', (int) $memberId);
        }

        [$rangeStart, $rangeEnd] = $this->resolveRange($monthCycle, $fromMonth, $toMonth, $fromDate, $toDate);

        if ($rangeStart) {
            $query->whereDate('entry_date', '>=', $rangeStart->toDateString());
        }
        if ($rangeEnd) {
            $query->whereDate('entry_date', '<=', $rangeEnd->toDateString());
        }

        $openingBalance = 0.0;
        if ($memberId !== '' && $rangeStart) {
            $openingBalance = (float) MemberLedger::query()
                ->where('member_id', (int) $memberId)
                ->whereDate('entry_date', '<', $rangeStart->toDateString())
                ->orderByDesc('entry_date')
                ->orderByDesc('id')
                ->value('balance_after');
        }

        $ledgerRows = $query->orderBy('entry_date')->orderBy('id')->get();
        $billings = Billing::query()
            ->whereIn('id', $ledgerRows->where('ref_type', 'BILL')->pluck('ref_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $running = $openingBalance;
        $rows = [];
        foreach ($ledgerRows as $ledgerRow) {
            $bill = $ledgerRow->ref_type === 'BILL' ? $billings->get($ledgerRow->ref_id) : null;
            $signedAmount = round((float) $ledgerRow->debit - (float) $ledgerRow->credit, 2);
            $running = round($running + $signedAmount, 2);

            $rows[] = [
                'date' => $ledgerRow->entry_date,
                'month_cycle' => $bill->month_cycle ?? optional($ledgerRow->entry_date)->format('Y-m'),
                'member_code' => $ledgerRow->member->member_code ?? '',
                'member_name' => $ledgerRow->member->name ?? '',
                'ref_type' => $ledgerRow->ref_type,
                'ref_id' => $ledgerRow->ref_id,
                'debit' => (float) $ledgerRow->debit,
                'credit' => (float) $ledgerRow->credit,
                'signed_amount' => $signedAmount,
                'balance_after' => $running,
                'reason_code' => $ledgerRow->reason_code,
                'active_days' => $bill->active_days ?? null,
                'rate_per_day' => $bill->rate_per_day ?? null,
                'base_amount' => $bill->base_amount ?? null,
                'extras_amount' => $bill->extras_amount ?? null,
                'net_payable' => $bill->net_payable ?? $signedAmount,
            ];
        }

        $totals = [
            'opening_balance' => $openingBalance,
            'debit' => (float) collect($rows)->sum('debit'),
            'credit' => (float) collect($rows)->sum('credit'),
            'closing_balance' => (float) (count($rows) ? collect($rows)->last()['balance_after'] : $openingBalance),
        ];

        if ($export === 'csv') {
            return Response::streamDownload(function () use ($rows, $totals) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Date', 'Month', 'Member Code', 'Member Name', 'Reference Type', 'Reference ID', 'Debit', 'Credit', 'Running Balance', 'Reason', 'Active Days', 'Rate/Day', 'Base Amount', 'Extras Amount', 'Net Payable']);
                fputcsv($out, ['', '', '', '', 'OPENING', '', '', '', number_format($totals['opening_balance'], 2, '.', ''), '', '', '', '', '', '']);
                foreach ($rows as $row) {
                    fputcsv($out, [
                        optional($row['date'])->format('Y-m-d'),
                        $row['month_cycle'],
                        $row['member_code'],
                        $row['member_name'],
                        $row['ref_type'],
                        $row['ref_id'],
                        number_format($row['debit'], 2, '.', ''),
                        number_format($row['credit'], 2, '.', ''),
                        number_format($row['balance_after'], 2, '.', ''),
                        $row['reason_code'],
                        $row['active_days'],
                        $row['rate_per_day'],
                        $row['base_amount'],
                        $row['extras_amount'],
                        $row['net_payable'],
                    ]);
                }
                fputcsv($out, []);
                fputcsv($out, ['Closing Balance', '', '', '', '', '', number_format($totals['debit'], 2, '.', ''), number_format($totals['credit'], 2, '.', ''), number_format($totals['closing_balance'], 2, '.', ''), '', '', '', '', '', '']);
                fclose($out);
            }, 'statement.csv', ['Content-Type' => 'text/csv']);
        }

        return view('admin.statement.index', compact(
            'members',
            'memberId',
            'memberQuery',
            'monthCycle',
            'fromMonth',
            'toMonth',
            'fromDate',
            'toDate',
            'rows',
            'totals'
        ));
    }

    private function resolveRange(string $monthCycle, string $fromMonth, string $toMonth, string $fromDate, string $toDate): array
    {
        $rangeStart = null;
        $rangeEnd = null;

        if ($monthCycle !== '') {
            try {
                $month = Carbon::createFromFormat('Y-m', $monthCycle);
                return [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()];
            } catch (\Throwable $e) {
            }
        }

        if ($fromMonth !== '') {
            try {
                $rangeStart = Carbon::createFromFormat('Y-m', $fromMonth)->startOfMonth();
            } catch (\Throwable $e) {
            }
        }
        if ($toMonth !== '') {
            try {
                $rangeEnd = Carbon::createFromFormat('Y-m', $toMonth)->endOfMonth();
            } catch (\Throwable $e) {
            }
        }
        if ($fromDate !== '') {
            try {
                $rangeStart = Carbon::parse($fromDate)->startOfDay();
            } catch (\Throwable $e) {
            }
        }
        if ($toDate !== '') {
            try {
                $rangeEnd = Carbon::parse($toDate)->endOfDay();
            } catch (\Throwable $e) {
            }
        }

        return [$rangeStart, $rangeEnd];
    }
}
