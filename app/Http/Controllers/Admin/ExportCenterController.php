<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\DepartmentLedger;
use App\Models\GuestMeal;
use App\Models\Member;
use App\Models\MemberLedger;
use App\Models\Payment;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExportCenterController extends Controller
{
    public function index(Request $request)
    {
        $memberId = trim((string) $request->query('member_id', ''));
        $monthCycle = trim((string) $request->query('month_cycle', ''));
        $fromDate = trim((string) $request->query('from_date', ''));
        $toDate = trim((string) $request->query('to_date', ''));
        $members = Member::query()->where('is_active', true)->orderBy('member_code')->get(['id', 'member_code', 'name']);

        return view('admin.exports.index', compact('members', 'memberId', 'monthCycle', 'fromDate', 'toDate'));
    }

    private function csv($name, $headers, $rows)
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }

    public function stockLedger()
    {
        $rows = StockTransaction::orderBy('txn_at')->get()->map(fn ($row) => [$row->txn_at, $row->item_id, $row->txn_type, $row->quantity, $row->unit_cost, $row->remarks]);
        return $this->csv('stock-ledger.csv', ['txn_at', 'item_id', 'txn_type', 'quantity', 'unit_cost', 'remarks'], $rows);
    }

    public function guestMeals(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDates($request);
        $query = GuestMeal::query()->with(['guest.department'])->orderBy('meal_date')->orderBy('id');

        if ($fromDate) {
            $query->whereDate('meal_date', '>=', $fromDate->toDateString());
        }
        if ($toDate) {
            $query->whereDate('meal_date', '<=', $toDate->toDateString());
        }

        $rows = $query->get()->map(fn ($row) => [
            optional($row->meal_date)->format('Y-m-d'),
            $row->guest_id,
            $row->guest?->name ?? '',
            $row->guest?->came_from ?? '',
            $row->guest?->department?->code ?? '',
            $row->meal_type,
            $row->quantity,
            $row->rate_applied ?? $row->rate,
            $row->amount,
            $row->guest?->remarks ?? '',
            $row->approved_at ? 'NO' : (($row->rate_applied ?? $row->rate) ? 'NO' : 'YES'),
        ]);

        return $this->csv('guest-meals.csv', ['date', 'guest_id', 'guest_name', 'company / came_from', 'department', 'meal_type', 'qty', 'rate', 'total_amount', 'remarks', 'rate_missing'], $rows);
    }

    public function departmentLedger()
    {
        $rows = DepartmentLedger::orderBy('entry_date')->get()->map(fn ($row) => [$row->entry_date, $row->department_id, $row->mess_id, $row->entry_type, $row->amount, $row->remarks]);
        return $this->csv('department-ledger.csv', ['entry_date', 'department_id', 'mess_id', 'entry_type', 'amount', 'remarks'], $rows);
    }

    public function bills(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDates($request);
        $monthCycle = trim((string) $request->query('month_cycle', ''));
        $memberId = trim((string) $request->query('member_id', ''));

        $query = Billing::query()->with('member')->orderBy('month_cycle')->orderBy('member_id');
        if ($monthCycle !== '') {
            $query->where('month_cycle', $monthCycle);
        }
        if ($memberId !== '') {
            $query->where('member_id', (int) $memberId);
        }
        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate->toDateString());
        }
        if ($toDate) {
            $query->where('created_at', '<=', $toDate->toDateString());
        }

        $rows = $query->get()->map(fn ($billing) => [
            $billing->month_cycle,
            $billing->member->member_code ?? '',
            $billing->member->name ?? '',
            $billing->active_days,
            $billing->rate_per_day,
            $billing->base_amount,
            $billing->extras_amount,
            $billing->net_payable,
            $billing->billing_status,
        ]);

        return $this->csv('bills.csv', ['month_cycle', 'member_code', 'member_name', 'active_days', 'rate_per_day', 'base_amount', 'extras_amount', 'net_payable', 'billing_status'], $rows);
    }

    public function payments(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDates($request);
        $memberId = trim((string) $request->query('member_id', ''));

        $query = Payment::query()->with(['member', 'bill'])->orderBy('payment_date')->orderBy('id');
        if ($memberId !== '') {
            $query->where('member_id', (int) $memberId);
        }
        if ($fromDate) {
            $query->whereDate('payment_date', '>=', $fromDate->toDateString());
        }
        if ($toDate) {
            $query->whereDate('payment_date', '<=', $toDate->toDateString());
        }

        $rows = $query->get()->map(fn ($payment) => [
            optional($payment->payment_date)->format('Y-m-d'),
            $payment->member->member_code ?? '',
            $payment->member->name ?? '',
            $payment->bill->month_cycle ?? '',
            $payment->method,
            $payment->reference_no,
            $payment->amount,
            $payment->status,
        ]);

        return $this->csv('payments.csv', ['payment_date', 'member_code', 'member_name', 'month_cycle', 'method', 'reference_no', 'amount', 'status'], $rows);
    }

    public function memberLedger(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDates($request);
        $memberId = trim((string) $request->query('member_id', ''));

        $query = MemberLedger::query()->with('member')->orderBy('entry_date')->orderBy('id');
        if ($memberId !== '') {
            $query->where('member_id', (int) $memberId);
        }
        if ($fromDate) {
            $query->whereDate('entry_date', '>=', $fromDate->toDateString());
        }
        if ($toDate) {
            $query->whereDate('entry_date', '<=', $toDate->toDateString());
        }

        $rows = $query->get()->map(fn ($ledger) => [
            optional($ledger->entry_date)->format('Y-m-d'),
            $ledger->member->member_code ?? '',
            $ledger->member->name ?? '',
            $ledger->ref_type,
            $ledger->ref_id,
            $ledger->debit,
            $ledger->credit,
            $ledger->balance_after,
            $ledger->reason_code,
        ]);

        return $this->csv('member-ledger.csv', ['entry_date', 'member_code', 'member_name', 'ref_type', 'ref_id', 'debit', 'credit', 'balance_after', 'reason_code'], $rows);
    }

    public function statement(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDates($request);
        $memberId = trim((string) $request->query('member_id', ''));
        $monthCycle = trim((string) $request->query('month_cycle', ''));

        $query = MemberLedger::query()->with('member')->whereIn('ref_type', ['BILL', 'PAYMENT', 'ADJUSTMENT']);
        if ($memberId !== '') {
            $query->where('member_id', (int) $memberId);
        }
        if ($monthCycle !== '') {
            try {
                $month = Carbon::createFromFormat('Y-m', $monthCycle);
                $fromDate = $month->copy()->startOfMonth();
                $toDate = $month->copy()->endOfMonth();
            } catch (\Throwable $e) {
            }
        }
        if ($fromDate) {
            $query->whereDate('entry_date', '>=', $fromDate->toDateString());
        }
        if ($toDate) {
            $query->whereDate('entry_date', '<=', $toDate->toDateString());
        }

        $openingBalance = 0.0;
        if ($memberId !== '' && $fromDate) {
            $openingBalance = (float) MemberLedger::query()
                ->where('member_id', (int) $memberId)
                ->whereDate('entry_date', '<', $fromDate->toDateString())
                ->orderByDesc('entry_date')
                ->orderByDesc('id')
                ->value('balance_after');
        }

        $running = $openingBalance;
        $rows = $query->orderBy('entry_date')->orderBy('id')->get()->map(function ($ledger) use (&$running) {
            $signed = (float) $ledger->debit - (float) $ledger->credit;
            $running = round($running + $signed, 2);

            return [
                optional($ledger->entry_date)->format('Y-m-d'),
                $ledger->member->member_code ?? '',
                $ledger->member->name ?? '',
                $ledger->ref_type,
                $ledger->ref_id,
                $ledger->debit,
                $ledger->credit,
                $running,
                $ledger->reason_code,
            ];
        });

        array_unshift($rows, ['', '', '', 'OPENING', '', '', '', number_format($openingBalance, 2, '.', ''), '']);

        return $this->csv('statement.csv', ['entry_date', 'member_code', 'member_name', 'ref_type', 'ref_id', 'debit', 'credit', 'running_balance', 'reason_code'], $rows);
    }

    private function resolveDates(Request $request): array
    {
        $fromDate = trim((string) $request->query('from_date', ''));
        $toDate = trim((string) $request->query('to_date', ''));

        try {
            $from = $fromDate !== '' ? Carbon::parse($fromDate)->startOfDay() : null;
        } catch (\Throwable $e) {
            $from = null;
        }
        try {
            $to = $toDate !== '' ? Carbon::parse($toDate)->endOfDay() : null;
        } catch (\Throwable $e) {
            $to = null;
        }

        return [$from, $to];
    }
}
