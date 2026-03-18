<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\Member;
use App\Models\MemberLedger;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $monthCycle = (string)$request->input('month_cycle', '');
        $memberId = (string)$request->input('member_id', '');

        $members = Member::query()->where('is_active', true)->orderBy('member_code')->get();
        $recoveryRows = [];
        $ledgerRows = collect();

        if ($monthCycle !== '') {
            $bills = Billing::query()->with('member')->where('month_cycle', $monthCycle)->get();
            foreach ($bills as $b) {
                $paid = (float)Payment::query()
                    ->where('member_id', $b->member_id)
                    ->where('status', 'APPROVED')
                    ->whereBetween('payment_date', [$monthCycle.'-01', date('Y-m-t', strtotime($monthCycle.'-01'))])
                    ->sum('amount');
                $outstanding = (float)$b->net_payable - $paid;
                $recoveryRows[] = [
                    'member_code'=>$b->member->member_code ?? '',
                    'net_payable'=>(float)$b->net_payable,
                    'paid'=>$paid,
                    'adjustment'=>0,
                    'outstanding'=>$outstanding,
                ];
            }
        }

        if ($memberId !== '') {
            $ledgerRows = MemberLedger::query()->with('member')->where('member_id', (int)$memberId)->orderBy('entry_date')->orderBy('id')->get();
        }

        return view('admin.reports.index', compact('monthCycle','memberId','members','recoveryRows','ledgerRows'));
    }
}
