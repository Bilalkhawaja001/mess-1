<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\Payments\PaymentAttemptService;
use App\Services\Payments\PaymentTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $member = $user?->linkedMember ?: $user?->member;
        abort_unless($member, 403, 'Member profile missing.');

        $bills = Billing::query()
            ->where('member_id', $member->id)
            ->orderByDesc('month_cycle')
            ->limit(24)
            ->get();

        $payments = Payment::query()
            ->with(['methodRecord', 'bill'])
            ->where('member_id', $member->id)
            ->latest('id')
            ->limit(200)
            ->get();

        $methods = PaymentMethod::query()->where('is_active', true)->orderBy('name')->get();

        return view('member.payments.index', compact('member', 'bills', 'payments', 'methods'));
    }

    public function initiate(Request $request, PaymentAttemptService $attemptService, PaymentTransactionService $transactionService): RedirectResponse
    {
        $payload = $request->validate([
            'bill_id' => ['required', 'integer', 'exists:billings,id'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'idempotency_key' => ['nullable', 'string', 'max:120'],
        ]);

        $user = Auth::user();
        $member = $user?->linkedMember ?: $user?->member;
        abort_unless($member, 403, 'Member profile missing.');

        $bill = Billing::query()->whereKey((int) $payload['bill_id'])->where('member_id', $member->id)->first();
        abort_unless($bill, 403, 'Bill access denied.');

        [$payment, $attempt] = $attemptService->createAttempt(
            (int) $member->id,
            (int) $bill->id,
            (int) $payload['payment_method_id'],
            (float) $payload['amount'],
            (int) Auth::id(),
        );

        $transactionService->recordFromAttempt($payment, $attempt, [
            'status' => Payment::STATUS_INITIATED,
            'merchant_ref' => $payload['reference_no'] ?? null,
            'idempotency_key' => $payload['idempotency_key'] ?? null,
            'raw_request_summary' => ['source' => 'member.portal'],
            'raw_response_summary' => ['note' => 'Attempt created, awaiting verification/callback.'],
        ], (int) Auth::id());

        return back()->with('success', 'Payment attempt initiated successfully. Pending verification.');
    }
}
