<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        // Frontend-only notification preview screen; no notification send side effects.
        return view('member.mobile.notifications');
    }
}
