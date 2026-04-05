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

        $stats = [
            'users' => User::query()->count(),
            'members' => Member::query()->count(),
            'open_cycles' => BillingCycle::query()->where('is_closed', false)->count(),
            'pending_payments' => Payment::query()->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_INITIATED, Payment::STATUS_RECONCILIATION_PENDING])->count(),
            'collections' => $totalCollected,
            'billable' => $totalBillable,
            'outstanding' => $outstanding,
            'recent_cycles' => BillingCycle::query()->latest('month_cycle')->limit(5)->get(),
            'recent_activity' => MemberLedger::query()->latest('entry_date')->latest('id')->limit(10)->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
