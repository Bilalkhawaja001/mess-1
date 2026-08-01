<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\Payments\DuplicateActivePaymentException;
use App\Services\Payments\PaymentDuplicateGuard;
use App\Services\Payments\PaymentReconciliationService;

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

        try {
            DB::transaction(function () use ($payment, $status, $request, $responseMessage, $rrn, $txnRef) {
                $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
                $duplicateGuard = app(PaymentDuplicateGuard::class);
                if ($status === Payment::STATUS_SUCCESS) {
                    $bill = $duplicateGuard->lockBill((int) $payment->bill_id, (int) $payment->member_id);
                    $monthCycle = (string) $bill->month_cycle;
                    $duplicateGuard->assertNoActiveDuplicate((int) $payment->member_id, $monthCycle, (int) $payment->id, (float) $payment->amount);
                    $duplicateGuard->applyGuardAttributes($payment, Payment::STATUS_SUCCESS, $monthCycle);
                }

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

            if ($status === Payment::STATUS_SUCCESS && $txn && ! $payment->reconciliations()->exists()) {
                app(PaymentReconciliationService::class)->createPending($payment->fresh(), $txn);
            }
            });
        } catch (DuplicateActivePaymentException $e) {
            return redirect()->route('member.payments.index')->with('error', $e->getMessage());
        }

        return redirect()->route('member.payments.index')
            ->with($status === Payment::STATUS_SUCCESS ? 'success' : 'warning', $responseMessage ?: 'JazzCash response received.');
    }

    public function ipn(Request $request): \Illuminate\Http\JsonResponse
    {
        Log::info('JazzCash IPN Received', $request->all());

        $result = $this->processJazzCashResponse($request);

        return response()->json([
            'ok' => $result['ok'],
            'message' => $result['message'],
            'payment_id' => $result['payment_id'] ?? null,
            'status' => $result['status'] ?? null,
        ], $result['http_status']);
    }

    private function processJazzCashResponse(Request $request): array
    {
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

            return [
                'ok' => false,
                'message' => 'Invalid JazzCash secure hash.',
                'http_status' => 400,
            ];
        }

        $paymentId = (int) $request->input('ppmpf_1');
        $responseCode = (string) $request->input('pp_ResponseCode');
        $responseMessage = (string) $request->input('pp_ResponseMessage');
        $rrn = $request->input('pp_RetreivalReferenceNo');
        $txnRef = $request->input('pp_TxnRefNo');

        $payment = Payment::query()->find($paymentId);

        if (! $payment) {
            return [
                'ok' => false,
                'message' => 'Payment not found.',
                'payment_id' => $paymentId,
                'http_status' => 404,
            ];
        }

        $status = $responseCode === '000'
            ? Payment::STATUS_SUCCESS
            : Payment::STATUS_FAILED;

        try {
            DB::transaction(function () use ($payment, $status, $request, $responseMessage, $rrn, $txnRef) {
                $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
                $duplicateGuard = app(PaymentDuplicateGuard::class);
                if ($status === Payment::STATUS_SUCCESS) {
                    $bill = $duplicateGuard->lockBill((int) $payment->bill_id, (int) $payment->member_id);
                    $monthCycle = (string) $bill->month_cycle;
                    $duplicateGuard->assertNoActiveDuplicate((int) $payment->member_id, $monthCycle, (int) $payment->id, (float) $payment->amount);
                    $duplicateGuard->applyGuardAttributes($payment, Payment::STATUS_SUCCESS, $monthCycle);
                }

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

            if ($status === Payment::STATUS_SUCCESS && $txn && ! $payment->reconciliations()->exists()) {
                app(PaymentReconciliationService::class)->createPending($payment->fresh(), $txn);
            }
            });
        } catch (DuplicateActivePaymentException $e) {
            return [
                'ok' => false,
                'message' => $e->getMessage(),
                'payment_id' => $payment->id,
                'status' => (string) $payment->status,
                'http_status' => 422,
            ];
        }

        return [
            'ok' => true,
            'message' => $responseMessage ?: 'JazzCash response processed.',
            'payment_id' => $payment->id,
            'status' => $status,
            'http_status' => 200,
        ];
    }


    public function statusInquiry(Payment $payment): \Illuminate\Http\JsonResponse
    {
        $txn = PaymentTransaction::query()
            ->where('payment_id', $payment->id)
            ->latest('id')
            ->first();

        if (! $txn || ! $txn->merchant_ref) {
            return response()->json([
                'ok' => false,
                'message' => 'No JazzCash transaction reference found for this payment.',
                'payment_id' => $payment->id,
            ], 422);
        }

        $payload = [
            'pp_TxnRefNo' => $txn->merchant_ref,
            'pp_MerchantID' => env('JAZZCASH_MERCHANT_ID'),
            'pp_Password' => env('JAZZCASH_PASSWORD'),
        ];

        $hashData = $payload;
        ksort($hashData);

        $salt = env('JAZZCASH_INTEGRITY_SALT');
        $payload['pp_SecureHash'] = strtoupper(hash_hmac(
            'sha256',
            $salt . '&' . implode('&', array_filter($hashData, fn ($v) => $v !== null && $v !== '')),
            $salt
        ));

        $url = env('JAZZCASH_STATUS_INQUIRY_URL');

        $response = Http::asJson()
            ->timeout(30)
            ->post($url, $payload);

        $responseBody = $response->json() ?? [];
        $updatedStatus = null;

        if ($response->successful() && (($responseBody['pp_ResponseCode'] ?? null) === '000')) {
            $paymentStatus = strtolower((string) ($responseBody['pp_Status'] ?? ''));
            $paymentCode = (string) ($responseBody['pp_PaymentResponseCode'] ?? '');

            if (in_array($paymentStatus, ['paid', 'success', 'successful', 'completed'], true) || $paymentCode === '000') {
                $updatedStatus = Payment::STATUS_SUCCESS;
            } elseif ($paymentStatus === 'failed' || ($paymentCode !== '' && $paymentCode !== '000')) {
                $updatedStatus = Payment::STATUS_FAILED;
            }

            if ($updatedStatus) {
                DB::transaction(function () use ($payment, $txn, $updatedStatus, $responseBody) {
                    $txn->status = $updatedStatus;
                    $txn->external_ref = $responseBody['pp_RetrievalReferenceNo'] ?? $txn->external_ref;
                    $txn->failure_reason = $updatedStatus === Payment::STATUS_FAILED
                        ? ($responseBody['pp_PaymentResponseMessage'] ?? $responseBody['pp_ResponseMessage'] ?? null)
                        : null;
                    $txn->raw_response_summary = [
                        'source' => 'status_inquiry',
                        'response' => $responseBody,
                    ];
                    $txn->completed_at = now();
                    $txn->verified_at = $updatedStatus === Payment::STATUS_SUCCESS ? now() : null;
                    $txn->save();

                    $payment->status = $updatedStatus;
                    $payment->reference_no = $txn->external_ref ?: $payment->reference_no;
                    $payment->approved_at = $updatedStatus === Payment::STATUS_SUCCESS ? now() : null;
                    $payment->last_transaction_id = $txn->id;
                    $payment->save();

                    if ($updatedStatus === Payment::STATUS_SUCCESS && ! $payment->reconciliations()->exists()) {
                        app(PaymentReconciliationService::class)->createPending($payment->fresh(), $txn);
                    }
                });
            }
        }

        Log::info('JazzCash Status Inquiry Response', [
            'payment_id' => $payment->id,
            'transaction_id' => $txn->id,
            'request' => $payload,
            'http_status' => $response->status(),
            'updated_status' => $updatedStatus,
            'response' => $responseBody ?: $response->body(),
        ]);

        return response()->json([
            'ok' => $response->successful(),
            'payment_id' => $payment->id,
            'transaction_id' => $txn->id,
            'http_status' => $response->status(),
            'updated_status' => $updatedStatus,
            'request' => $payload,
            'response' => $responseBody ?: $response->body(),
        ], $response->successful() ? 200 : 502);
    }

}
