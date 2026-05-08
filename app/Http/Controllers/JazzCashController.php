<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JazzCashController extends Controller
{
    public function return(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Log::info('JazzCash Return Received', $request->all());

        $receivedHash = strtoupper((string) $request->input('pp_SecureHash', ''));
        $hashData = $request->except('pp_SecureHash');
        ksort($hashData);

        $hashValues = [];
        foreach ($hashData as $value) {
            if ($value !== null && $value !== '') {
                $hashValues[] = $value;
            }
        }

        $salt = env('JAZZCASH_INTEGRITY_SALT');
        $calculatedHash = strtoupper(hash_hmac('sha256', $salt . '&' . implode('&', $hashValues), $salt));

        if (! hash_equals($calculatedHash, $receivedHash)) {
            Log::warning('JazzCash SecureHash mismatch', [
                'received' => $receivedHash,
                'calculated' => $calculatedHash,
                'payload' => $request->all(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Invalid JazzCash secure hash.',
            ], 400);
        }

        $paymentId = (int) $request->input('ppmpf_1');
        $responseCode = (string) $request->input('pp_ResponseCode');
        $responseMessage = (string) $request->input('pp_ResponseMessage');
        $rrn = $request->input('pp_RetreivalReferenceNo');
        $txnRef = $request->input('pp_TxnRefNo');

        $payment = Payment::query()->find($paymentId);

        if (! $payment) {
            return response()->json([
                'ok' => false,
                'message' => 'Payment not found',
                'data' => $request->all(),
            ], 404);
        }

        $status = $responseCode === '000'
            ? Payment::STATUS_SUCCESS
            : Payment::STATUS_FAILED;

        DB::transaction(function () use ($payment, $status, $request, $responseMessage, $rrn, $txnRef) {
            $txn = PaymentTransaction::query()
                ->where('payment_id', $payment->id)
                ->latest('id')
                ->first();

            if ($txn) {
                $txn->status = $status;
                $txn->external_ref = $rrn;
                $txn->merchant_ref = $txnRef;
                $txn->failure_reason = $status === Payment::STATUS_FAILED ? $responseMessage : null;
                $txn->raw_response_summary = $request->all();
                $txn->completed_at = now();
                $txn->verified_at = $status === Payment::STATUS_SUCCESS ? now() : null;
                $txn->save();

                $payment->last_transaction_id = $txn->id;
            }

            $payment->status = $status;
            $payment->reference_no = $rrn ?: $txnRef;
            $payment->approved_at = $status === Payment::STATUS_SUCCESS ? now() : null;
            $payment->save();
        });

        return redirect()->route('member.payments.index')
            ->with($status === Payment::STATUS_SUCCESS ? 'success' : 'warning', $responseMessage ?: 'JazzCash response received.');
    }
}
