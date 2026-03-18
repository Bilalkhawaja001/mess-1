<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\Member;
use App\Models\MemberLedger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatementController extends Controller
{
    public function index(Request $request): View
    {
        $memberId = (string)$request->input('member_id', '');
        $monthCycle = (string)$request->input('month_cycle', '');
        $fromMonth = (string)$request->input('from_month', '');
        $toMonth = (string)$request->input('to_month', '');

        $members = Member::query()->where('is_active', true)->orderBy('member_code')->get();

        $q = MemberLedger::query()->with('member')->whereIn('ref_type', ['BILL','PAYMENT','ADJUSTMENT']);
        if ($memberId !== '') { $q->where('member_id', (int)$memberId); }
        if ($monthCycle !== '') {
            $q->whereBetween('entry_date', [$monthCycle.'-01', date('Y-m-t', strtotime($monthCycle.'-01'))]);
        } else {
            if ($fromMonth !== '') { $q->whereDate('entry_date', '>=', $fromMonth.'-01'); }
            if ($toMonth !== '') { $q->whereDate('entry_date', '<=', date('Y-m-t', strtotime($toMonth.'-01'))); }
        }

        $ledgerRows = $q->orderBy('entry_date')->orderBy('id')->get();
        $running = 0.0; $rows = [];
        foreach ($ledgerRows as $lr) {
            $running = round($running + (float)$lr->debit - (float)$lr->credit, 2);
            $bill = $lr->ref_type === 'BILL' ? Billing::query()->find($lr->ref_id) : null;
            $rows[] = [
                'date' => $lr->entry_date,
                'month_cycle' => $bill->month_cycle ?? optional($lr->entry_date)->format('Y-m'),
                'member_code' => $lr->member->member_code ?? '',
                'ref_type' => $lr->ref_type,
                'ref_id' => $lr->ref_id,
                'debit' => (float)$lr->debit,
                'credit' => (float)$lr->credit,
                'signed_amount' => (float)$lr->debit - (float)$lr->credit,
                'balance_after' => $running,
                'active_days' => $bill->active_days ?? '',
                'rate_per_day' => $bill->rate_per_day ?? '',
                'base_amount' => $bill->base_amount ?? '',
                'extras_amount' => $bill->extras_amount ?? '',
                'net_payable' => $bill->net_payable ?? ((float)$lr->debit - (float)$lr->credit),
            ];
        }

        return view('admin.statement.index', compact('members','memberId','monthCycle','fromMonth','toMonth','rows'));
    }
}
