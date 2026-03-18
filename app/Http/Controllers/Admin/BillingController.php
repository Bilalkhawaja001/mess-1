<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\GenerateBillingRequest;
use App\Models\Billing;
use App\Services\Billing\BillingCorrectionService;
use App\Services\Billing\BillingGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request): View
    {
        $monthCycle = $request->input('month_cycle');

        $q = Billing::query()->with('member')->orderByDesc('month_cycle')->orderBy('member_id');
        if ($monthCycle) {
            $q->where('month_cycle', $monthCycle);
        }

        $rows = $q->limit(500)->get();
        $months = Billing::query()->select('month_cycle')->distinct()->orderByDesc('month_cycle')->pluck('month_cycle');

        return view('admin.billing.index', compact('rows', 'months', 'monthCycle'));
    }

    public function generate(GenerateBillingRequest $request, BillingGenerationService $service): RedirectResponse
    {
        $monthCycle = (string) $request->input('month_cycle');
        $ratePerDay = (float) $request->input('rate_per_day', 100);

        $result = $service->generate($monthCycle, (int) Auth::id(), $ratePerDay);

        if (($result['status'] ?? '') === 'already_generated') {
            return redirect()->route('admin.billing.index', ['month_cycle' => $monthCycle])
                ->with('info', "Billing already generated for this scope (scope_hash={$result['scope_hash']}). No inserts.");
        }

        return redirect()->route('admin.billing.index', ['month_cycle' => $monthCycle])
            ->with('success', "Billing generated. inserted={$result['inserted']} skipped={$result['skipped']}");
    }

    public function correct(Billing $billing, Request $request, BillingCorrectionService $service): RedirectResponse
    {
        $payload = $request->validate([
            'new_net_payable' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $service->correct($billing, (float) $payload['new_net_payable'], (string) $payload['reason'], (int) Auth::id());

        return redirect()->route('admin.billing.index', ['month_cycle' => $billing->month_cycle])->with('success', 'Billing corrected.');
    }
}
