<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatementController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $members = DB::table('members')
            ->select('id', 'member_code', 'name', 'department_name', 'mess_id', 'join_date', 'leave_date')
            ->orderBy('member_code')
            ->orderBy('name')
            ->get();

        $memberId = (int) $request->input('member_id', 0);
        if ($memberId <= 0 && $members->isNotEmpty()) {
            $memberId = (int) $members->first()->id;
        }

        $singleMonth = trim((string) $request->input('single_month', $request->input('month_cycle', '')));
        $fromMonth = trim((string) $request->input('from_month', ''));
        $toMonth = trim((string) $request->input('to_month', ''));

        if ($singleMonth !== '') {
            $fromMonth = $singleMonth;
            $toMonth = $singleMonth;
        }

        if ($fromMonth === '' || $toMonth === '') {
            $latestMonth = DB::table('member_ledgers')
                ->where('member_id', $memberId)
                ->selectRaw("DATE_FORMAT(entry_date, '%Y-%m') as ym")
                ->orderByDesc('entry_date')
                ->value('ym');

            $fromMonth = $fromMonth ?: ($latestMonth ?: now()->format('Y-m'));
            $toMonth = $toMonth ?: $fromMonth;
        }

        $monthPattern = '/^\\d{4}-(0[1-9]|1[0-2])$/';

        if (! preg_match($monthPattern, $fromMonth)) {
            $fromMonth = now()->format('Y-m');
        }

        if (! preg_match($monthPattern, $toMonth)) {
            $toMonth = $fromMonth;
        }

        try {
            $fromDate = Carbon::createFromFormat('Y-m', $fromMonth)->startOfMonth()->toDateString();
            $toDate = Carbon::createFromFormat('Y-m', $toMonth)->endOfMonth()->toDateString();
        } catch (\Throwable $e) {
            $fromMonth = now()->format('Y-m');
            $toMonth = $fromMonth;
            $fromDate = now()->startOfMonth()->toDateString();
            $toDate = now()->endOfMonth()->toDateString();
        }

        $member = $memberId > 0
            ? DB::table('members')->where('id', $memberId)->first()
            : null;

        $messName = '-';
        if ($member && !empty($member->mess_id) && Schema::hasTable('messes')) {
            $messName = DB::table('messes')->where('id', $member->mess_id)->value('name') ?: '-';
        }

        $openingBalance = (float) (DB::table('member_ledgers')
            ->where('member_id', $memberId)
            ->where('entry_date', '<', $fromDate)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->value('balance_after') ?? 0);

        $ledgerRows = DB::table('member_ledgers')
            ->where('member_id', $memberId)
            ->whereBetween('entry_date', [$fromDate, $toDate])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $billIds = $ledgerRows
            ->where('ref_type', 'BILL')
            ->pluck('ref_id')
            ->filter()
            ->unique()
            ->values();

        $paymentIds = $ledgerRows
            ->where('ref_type', 'PAYMENT')
            ->pluck('ref_id')
            ->filter()
            ->unique()
            ->values();

        $billings = $billIds->isNotEmpty()
            ? DB::table('billings')->whereIn('id', $billIds)->get()->keyBy('id')
            : collect();

        $payments = $paymentIds->isNotEmpty()
            ? DB::table('payments')->whereIn('id', $paymentIds)->get()->keyBy('id')
            : collect();

        $rows = $ledgerRows->map(function ($row) use ($billings, $payments) {
            $refType = strtoupper((string) $row->ref_type);
            $bill = $refType === 'BILL' ? ($billings[$row->ref_id] ?? null) : null;
            $payment = $refType === 'PAYMENT' ? ($payments[$row->ref_id] ?? null) : null;

            $month = $bill->month_cycle
                ?? ($payment && !empty($payment->payment_date) ? Carbon::parse($payment->payment_date)->format('Y-m') : Carbon::parse($row->entry_date)->format('Y-m'));

            return (object) [
                'month' => $month,
                'days' => $bill->active_days ?? '',
                'rate_per_day' => $bill->rate_per_day ?? '',
                'total_amount' => $bill->net_payable ?? (((float) $row->debit) > 0 ? $row->debit : (((float) $row->credit) * -1)),
                'ref_type' => $refType,
                'ref_id' => $row->ref_id,
                'debit' => (float) $row->debit,
                'credit' => (float) $row->credit,
                'running_balance' => (float) $row->balance_after,
            ];
        });

        $totalDebit = (float) $ledgerRows->sum('debit');
        $totalCredit = (float) $ledgerRows->sum('credit');
        $closingBalance = $rows->isNotEmpty()
            ? (float) $rows->last()->running_balance
            : $openingBalance;

        if ($request->input('export') === 'csv') {
            return $this->csvResponse($member, $messName, $fromMonth, $toMonth, $openingBalance, $totalDebit, $totalCredit, $closingBalance, $rows);
        }

        return view('admin.statement.index', compact(
            'members',
            'memberId',
            'member',
            'messName',
            'singleMonth',
            'fromMonth',
            'toMonth',
            'openingBalance',
            'totalDebit',
            'totalCredit',
            'closingBalance',
            'rows'
        ));
    }

    private function csvResponse($member, string $messName, string $fromMonth, string $toMonth, float $openingBalance, float $totalDebit, float $totalCredit, float $closingBalance, $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($member, $messName, $fromMonth, $toMonth, $openingBalance, $totalDebit, $totalCredit, $closingBalance, $rows) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Executive Mess']);
            fputcsv($out, ['Member Account Statement']);
            fputcsv($out, ['Member ID', $member->member_code ?? '']);
            fputcsv($out, ['Name', $member->name ?? '']);
            fputcsv($out, ['Department', $member->department_name ?? '']);
            fputcsv($out, ['Mess', $messName]);
            fputcsv($out, ['Statement Month', $fromMonth.' to '.$toMonth]);
            fputcsv($out, []);
            fputcsv($out, ['Opening Balance', 'Total Debit', 'Total Credit', 'Closing Balance']);
            fputcsv($out, [$openingBalance, $totalDebit, $totalCredit, $closingBalance]);
            fputcsv($out, []);
            fputcsv($out, ['Month', 'Days', 'Rate Per Day', 'Total Amount', 'Ref Type', 'Ref ID', 'Debit', 'Credit', 'Running Balance']);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->month,
                    $row->days,
                    $row->rate_per_day,
                    $row->total_amount,
                    $row->ref_type,
                    $row->ref_id,
                    $row->debit,
                    $row->credit,
                    $row->running_balance,
                ]);
            }

            fclose($out);
        }, 'statement.csv', ['Content-Type' => 'text/csv']);
    }
}
