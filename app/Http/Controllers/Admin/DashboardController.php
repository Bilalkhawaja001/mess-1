<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\BillingCycle;
use App\Models\Member;
use App\Models\MemberLedger;
use App\Models\Payment;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalBillable = (float) Billing::query()->sum('net_payable');
        $totalCollected = (float) MemberLedger::query()->sum('credit');
        $outstanding = round($totalBillable - $totalCollected, 2);

        $recentCycles = BillingCycle::query()->latest('month_cycle')->limit(5)->get()->map(function (BillingCycle $cycle) {
            return [
                'month_cycle' => $cycle->month_cycle,
                'status' => $cycle->status,
                'summary' => $cycle->is_closed ? 'Closed cycle' : 'Open cycle',
            ];
        })->all();

        $recentActivity = MemberLedger::query()->with('member')->latest('entry_date')->latest('id')->limit(10)->get()->map(function (MemberLedger $ledger) {
            return [
                'title' => trim(($ledger->member->member_code ?? 'Member') . ' ' . $ledger->ref_type . ' #' . $ledger->ref_id),
                'time' => optional($ledger->entry_date)->format('Y-m-d') ?? '',
            ];
        })->all();

        $stats = [
            'users' => User::query()->count(),
            'members' => Member::query()->count(),
            'open_cycles' => BillingCycle::query()->where('is_closed', false)->count(),
            'pending_payments' => Payment::query()->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_INITIATED, Payment::STATUS_RECONCILIATION_PENDING])->count(),
            'collections' => $totalCollected,
            'collected' => $totalCollected,
            'billable' => $totalBillable,
            'outstanding' => $outstanding,
            'recent_cycles' => $recentCycles,
            'recentCycles' => $recentCycles,
            'recent_activity' => $recentActivity,
            'recentActivity' => $recentActivity,
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
