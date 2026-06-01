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
    public function index(): View|RedirectResponse
    {
        $user = Auth::user();
        $member = $user?->resolvedMemberProfile();

        if (! $member) {
            return redirect()->route('member.dashboard')->with('warning', 'Your member profile is not linked yet. Please contact admin.');
        }

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

        $view = request()->routeIs('member.bill', 'member.app.bill')
            ? 'member.mobile.bill'
            : 'member.mobile.payments';

        return view($view, compact('member', 'bills', 'payments', 'methods'));
    }

    public function initiate(Request $request, PaymentAttemptService $attemptService, PaymentTransactionService $transactionService): View|RedirectResponse
    {
        $payload = $request->validate([
            'bill_id' => ['required', 'integer', 'exists:billings,id'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'idempotency_key' => ['nullable', 'string', 'max:120'],
        ]);

        $user = Auth::user();
        $member = $user?->resolvedMemberProfile();

        if (! $member) {
            return redirect()->route('member.dashboard')->with('warning', 'Your member profile is not linked yet. Please contact admin.');
        }

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

        $method = PaymentMethod::query()->find((int) $payload['payment_method_id']);

        if ($method && strtoupper((string) $method->code) === 'JAZZCASH') {

            $now = now();
            $txnDateTime = $now->format('YmdHis');
            $txnExpiryDateTime = $now->copy()->addDay()->format('YmdHis');
            $txnRefNo = 'T' . $txnDateTime . $payment->id;

            $jazzcashPayload = [
                'pp_Version' => '2.0',
                'pp_TxnType' => '',
                'pp_IsRegisteredCustomer' => 'No',
                'pp_TokenizedCardNumber' => '',
                'pp_CustomerID' => (string) $member->id,
                'pp_CustomerEmail' => '',
                'pp_CustomerMobile' => '',
                'pp_MerchantID' => env('JAZZCASH_MERCHANT_ID'),
                'pp_Language' => 'EN',
                'pp_SubMerchantID' => '',
                'pp_Password' => env('JAZZCASH_PASSWORD'),
                'pp_TxnRefNo' => $txnRefNo,
                'pp_Amount' => (string) ((int) round(((float) $payload['amount']) * 100)),
                'pp_DiscountedAmount' => '',
                'pp_DiscountBank' => '',
                'pp_TxnCurrency' => 'PKR',
                'pp_TxnDateTime' => $txnDateTime,
                'pp_TxnExpiryDateTime' => $txnExpiryDateTime,
                'pp_BillReference' => 'BILL' . $bill->id,
                'pp_Description' => 'Mess Bill Payment',
                'pp_ReturnURL' => env('JAZZCASH_RETURN_URL'),
                'ppmpf_1' => (string) $payment->id,
                'ppmpf_2' => (string) $bill->id,
                'ppmpf_3' => (string) $member->id,
                'ppmpf_4' => '',
                'ppmpf_5' => '',
            ];

            $salt = env('JAZZCASH_INTEGRITY_SALT');
            $hashValues = [];
            foreach ($jazzcashPayload as $value) {
                if ($value !== null && $value !== '') {
                    $hashValues[] = $value;
                }
            }

            $jazzcashPayload['pp_SecureHash'] = hash_hmac('sha256', $salt . '&' . implode('&', $hashValues), $salt);

            return view('member.payments.jazzcash-redirect', [
                'postUrl' => 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/',
                'payload' => $jazzcashPayload,
            ]);
        }

        return back()->with('success', 'Payment attempt initiated successfully. Pending verification.');
    }
}
