<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\GenerateBillingRequest;
use App\Models\Billing;
use App\Models\MonthClosure;
use App\Models\Mess;
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
        $monthCycle = (string) $request->input('month_cycle', '');

        $q = Billing::query()->with('member')->orderByDesc('month_cycle')->orderBy('member_id');
        if ($monthCycle !== '') {
            $q->where('month_cycle', $monthCycle);
        }

        $rows = $q->limit(500)->get();
        $months = Billing::query()->select('month_cycle')->distinct()->orderByDesc('month_cycle')->pluck('month_cycle');
        $monthClosures = MonthClosure::query()->orderByDesc('month_cycle')->limit(24)->get()->keyBy('month_cycle');
        $isSuperAdmin = Auth::user()?->role?->code === 'SUPER_ADMIN';
        $messes = Mess::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.billing.index', compact('rows', 'months', 'monthCycle', 'monthClosures', 'isSuperAdmin', 'messes'));
    }

    public function generate(GenerateBillingRequest $request, BillingGenerationService $service): RedirectResponse
    {
        $monthCycle = (string) $request->input('month_cycle');

        $result = $service->generate($monthCycle, (int) Auth::id());

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


    public function bulkRateCorrection(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'month_cycle' => ['required', 'string', 'max:7'],
            'mess_id' => ['required', 'integer', 'exists:messes,id'],
            'old_rate' => ['required', 'numeric', 'min:0'],
            'new_rate' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $monthCycle = (string) $payload['month_cycle'];
        $messId = (int) $payload['mess_id'];
        $oldRate = round((float) $payload['old_rate'], 2);
        $newRate = round((float) $payload['new_rate'], 2);
        $reason = (string) $payload['reason'];
        $userId = (int) Auth::id();

        if ($oldRate === $newRate) {
            return redirect()->route('admin.billing.index', ['month_cycle' => $monthCycle])
                ->withErrors(['new_rate' => 'Old rate and new rate are same.']);
        }

        $billings = Billing::query()
            ->with('member')
            ->where('month_cycle', $monthCycle)
            ->where('billing_status', 'POSTED')
            ->whereHas('member', fn ($q) => $q->where('mess_id', $messId))
            ->get()
            ->filter(fn (Billing $billing) => round((float) $billing->rate_per_day, 2) === $oldRate)
            ->values();

        if ($billings->isEmpty()) {
            return redirect()->route('admin.billing.index', ['month_cycle' => $monthCycle])
                ->withErrors(['month_cycle' => 'No posted billings found for selected month, mess, and old rate.']);
        }

        $affected = 0;
        $totalCredit = 0.0;
        $totalDebit = 0.0;

        \Illuminate\Support\Facades\DB::transaction(function () use ($billings, $newRate, $reason, $userId, &$affected, &$totalCredit, &$totalDebit) {
            foreach ($billings as $billing) {
                $billing->refresh();

                $beforeNet = (float) $billing->net_payable;
                $newBase = round(((int) $billing->active_days) * $newRate, 2);
                $newNet = round($newBase + (float) $billing->extras_amount, 2);
                $delta = round($newNet - $beforeNet, 2);

                $before = $billing->toArray();

                $billing->rate_per_day = $newRate;
                $billing->base_amount = $newBase;
                $billing->net_payable = $newNet;
                $billing->correction_reason = $reason;
                $billing->generated_by_user_id = $userId;
                $billing->save();

                \App\Models\MemberLedger::query()
                    ->where('member_id', $billing->member_id)
                    ->where('ref_type', 'BILL_CORRECTION')
                    ->where('ref_id', $billing->id)
                    ->delete();

                if ($delta !== 0.0) {
                    $lastBal = (float) (\App\Models\MemberLedger::query()
                        ->where('member_id', $billing->member_id)
                        ->orderByDesc('entry_date')
                        ->orderByDesc('id')
                        ->value('balance_after') ?? 0);

                    \App\Models\MemberLedger::query()->create([
                        'member_id' => $billing->member_id,
                        'entry_date' => now()->toDateString(),
                        'debit' => $delta > 0 ? abs($delta) : 0,
                        'credit' => $delta < 0 ? abs($delta) : 0,
                        'ref_type' => 'BILL_CORRECTION',
                        'ref_id' => $billing->id,
                        'balance_after' => round($lastBal + $delta, 2),
                        'reason_code' => 'BULK_RATE_CORRECTION',
                        'is_opening_balance' => false,
                        'posted_by_user_id' => $userId,
                    ]);

                    if ($delta < 0) {
                        $totalCredit += abs($delta);
                    } else {
                        $totalDebit += abs($delta);
                    }
                }

                app(\App\Services\LedgerToolchainService::class)->recompute((int) $billing->member_id);
                app(\App\Services\AuditLogService::class)->log('billing.bulk_rate_corrected', Billing::class, (int) $billing->id, $before, $billing->toArray(), $reason);

                $affected++;
            }
        });

        return redirect()->route('admin.billing.index', ['month_cycle' => $monthCycle])
            ->with('success', 'Mess-wise bulk rate correction posted. affected='.$affected.' credit='.number_format($totalCredit, 2).' debit='.number_format($totalDebit, 2));
    }


    public function updateDueDate(\Illuminate\Http\Request $request, \App\Models\Billing $billing): \Illuminate\Http\RedirectResponse
    {
        $payload = $request->validate([
            'due_date' => ['nullable', 'date'],
        ]);

        $billing->update([
            'due_date' => $payload['due_date'] ?? null,
        ]);

        return back()->with('success', 'Due date updated successfully.');
    }


}
