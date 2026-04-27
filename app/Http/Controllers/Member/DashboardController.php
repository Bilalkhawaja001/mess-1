<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $member = $user?->resolvedMemberProfile();

        return view('member.dashboard', [
            'user' => $user,
            'member' => $member,
            'memberProfileMissing' => $user?->isMemberRole() && ! $member,
        ]);
    }
}
