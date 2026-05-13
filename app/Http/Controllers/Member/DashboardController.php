<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\Complaint;
use App\Models\MemberLedger;
use App\Models\Menu;
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
        $lastBill = null;
        $lastPayment = null;
        $openComplaintsCount = 0;
        $recentLedgerEntries = collect();
        $recentComplaints = collect();
        $todayMenu = [
            'BREAKFAST' => '-',
            'LUNCH' => '-',
            'DINNER' => '-',
            'TEA_OTHER' => '-',
        ];

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

            $lastBill = Billing::query()
                ->where('member_id', $member->id)
                ->orderByDesc('month_cycle')
                ->orderByDesc('id')
                ->first();

            $recentLedgerEntries = collect();

            $recentComplaints = Complaint::query()
                ->where('member_id', $member->id)
                ->latest('id')
                ->limit(3)
                ->get();

            $todayMenuRows = Menu::query()
                ->where('status', Menu::STATUS_APPROVED)
                ->whereDate('menu_date', now()->toDateString())
                ->where('mess_id', $member->mess_id)
                ->orderBy('menu_date')
                ->get();

            foreach ($todayMenuRows as $row) {
                $bucket = in_array($row->meal_type, ['TEA', 'OTHER'], true) ? 'TEA_OTHER' : $row->meal_type;
                if (! array_key_exists($bucket, $todayMenu)) {
                    continue;
                }

                $value = trim($row->title."\n".$row->items_text);
                $todayMenu[$bucket] = $todayMenu[$bucket] === '-' ? $value : ($todayMenu[$bucket]."\n\n".$value);
            }
        }

        return view('member.dashboard', [
            'user' => $user,
            'member' => $member,
            'memberProfileMissing' => $user?->isMemberRole() && ! $member,
            'outstandingAmount' => $outstandingAmount,
            'currentMonthBill' => $currentMonthBill,
            'lastBill' => $lastBill,
            'lastPayment' => $lastPayment,
            'openComplaintsCount' => $openComplaintsCount,
            'recentLedgerEntries' => $recentLedgerEntries,
            'recentComplaints' => $recentComplaints,
            'todayMenu' => $todayMenu,
        ]);
    }
}
