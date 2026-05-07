<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\Complaint;
use App\Models\MemberLedger;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $member = $user?->resolvedMemberProfile();
        $outstandingAmount = 0.0;
        $currentMonthBill = 0.0;
        $lastPayment = null;
        $openComplaintsCount = 0;
        $recentLedgerEntries = collect();
        $recentComplaints = collect();

        if ($member) {
            $outstandingAmount = (float) (MemberLedger::query()
                ->where('member_id', $member->id)
                ->orderByDesc('entry_date')
                ->orderByDesc('id')
                ->value('balance_after') ?? 0);

            $currentMonthBill = (float) (Billing::query()
                ->where('member_id', $member->id)
                ->where('month_cycle', now()->format('Y-m'))
                ->value('net_payable') ?? 0);

            $lastPayment = Payment::query()
                ->where('member_id', $member->id)
                ->latest('id')
                ->first();

            $openComplaintsCount = Complaint::query()
                ->where('member_id', $member->id)
                ->whereNotIn('status', [Complaint::STATUS_CLOSED, Complaint::STATUS_REJECTED, Complaint::STATUS_RESOLVED])
                ->count();

            $recentLedgerEntries = MemberLedger::query()
                ->where('member_id', $member->id)
                ->orderByDesc('entry_date')
                ->orderByDesc('id')
                ->limit(3)
                ->get();

            $recentComplaints = Complaint::query()
                ->where('member_id', $member->id)
                ->latest('id')
                ->limit(3)
                ->get();
        }

        return view('member.dashboard', [
            'user' => $user,
            'member' => $member,
            'memberProfileMissing' => $user?->isMemberRole() && ! $member,
            'outstandingAmount' => $outstandingAmount,
            'currentMonthBill' => $currentMonthBill,
            'lastPayment' => $lastPayment,
            'openComplaintsCount' => $openComplaintsCount,
            'recentLedgerEntries' => $recentLedgerEntries,
            'recentComplaints' => $recentComplaints,
        ]);
    }
}
