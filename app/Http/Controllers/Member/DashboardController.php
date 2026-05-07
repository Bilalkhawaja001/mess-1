<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MemberLedger;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $member = $user?->resolvedMemberProfile();
        $outstandingAmount = 0.0;

        if ($member) {
            $outstandingAmount = (float) (MemberLedger::query()
                ->where('member_id', $member->id)
                ->orderByDesc('entry_date')
                ->orderByDesc('id')
                ->value('balance_after') ?? 0);
        }

        return view('member.dashboard', [
            'user' => $user,
            'member' => $member,
            'memberProfileMissing' => $user?->isMemberRole() && ! $member,
            'outstandingAmount' => $outstandingAmount,
        ]);
    }
}
