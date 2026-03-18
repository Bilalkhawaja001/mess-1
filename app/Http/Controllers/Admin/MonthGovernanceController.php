<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonthClosure;
use App\Services\MonthClosureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MonthGovernanceController extends Controller
{
    public function __construct(private readonly MonthClosureService $monthClosureService)
    {
    }

    public function index(): View
    {
        $rows = MonthClosure::query()->orderByDesc('month_cycle')->limit(24)->get();
        return view('admin.month.index', compact('rows'));
    }

    public function close(Request $request): RedirectResponse
    {
        $payload = $request->validate(['month_cycle' => ['required', 'regex:/^\\d{4}-\\d{2}$/'], 'reason' => ['required', 'string', 'max:500']]);
        $this->monthClosureService->close((string) $payload['month_cycle'], (int) Auth::id(), (string) $payload['reason']);
        return back()->with('success', 'Month closed successfully.');
    }

    public function reopen(Request $request): RedirectResponse
    {
        $payload = $request->validate(['month_cycle' => ['required', 'regex:/^\\d{4}-\\d{2}$/'], 'reason' => ['required', 'string', 'max:500']]);
        $this->monthClosureService->reopen((string) $payload['month_cycle'], (int) Auth::id(), (string) $payload['reason']);
        return back()->with('success', 'Month reopened successfully.');
    }

    public function hardReset(Request $request): RedirectResponse
    {
        $payload = $request->validate(['month_cycle' => ['required', 'regex:/^\\d{4}-\\d{2}$/'], 'reason' => ['required', 'string', 'max:500']]);
        $this->monthClosureService->hardReset((string) $payload['month_cycle'], (int) Auth::id(), (string) $payload['reason']);
        return back()->with('success', 'Month hard reset completed.');
    }
}
