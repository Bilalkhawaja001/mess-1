<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingCycle;
use App\Models\Member;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'users' => User::query()->count(),
            'members' => Member::query()->count(),
            'open_cycles' => BillingCycle::query()->where('is_closed', false)->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
