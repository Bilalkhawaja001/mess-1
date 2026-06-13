<?php

namespace App\Services\Payments;

use App\Models\MemberDeviceToken;
use App\Models\Payment;
use App\Models\PaymentReconciliation;
use App\Models\PaymentTransaction;
use App\Services\AuditLogService;
use App\Services\Firebase\FirebaseNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentReconciliationService
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly AuditLogService $auditLogService,
        private readonly FirebaseNotificationService $firebaseNotificationService,
        private readonly PaymentDuplicateGuard $duplicateGuard,
    ) {
    }

    public function createPending(Payment $payment, ?PaymentTransaction $txn = null): PaymentReconciliation
    {
        $row = PaymentReconciliation::query()->create([
            'payment_id' => $payment->id,
            'payment_transaction_id' => $txn?->id,
            'member_id' => $payment->member_id,
            'bill_id' => $payment->bill_id,
            'status' => Payment::STATUS_RECONCILIATION_PENDING,
            'ledger_sync_status' => 'PENDING',
            'accounting_sync_status' => 'PENDING',
            'meta' => ['hook' => 'ledger/accounting-safe-post-ready'],
        ]);

        $this->paymentService->transition($payment, Payment::STATUS_RECONCILIATION_PENDING, 'reconciliation-pending-created');
        $this->auditLogService->log('payment.reconciliation_changed', PaymentReconciliation::class, (int) $row->id, [], $row->toArray(), 'pending');

        DB::afterCommit(function () use ($payment) {
            $this->sendPaymentReceivedNotification($payment->fresh());
        });

        return $row;
    }


    private function sendPaymentReceivedNotification(Payment $payment): void
    {
        $tokens = MemberDeviceToken::query()
            ->where('member_id', $payment->member_id)
            ->pluck('device_token')
            ->filter()
            ->values();

        if ($tokens->isEmpty()) {
            return;
        }

        $amount = number_format((float) $payment->amount, 2);

        foreach ($tokens as $token) {
            try {
                $result = $this->firebaseNotificationService->sendToToken(
                    (string) $token,
                    'Payment received',
                    "Your payment of PKR {$amount} has been received and is pending reconciliation.",
                    [
                        'type' => 'payment_received',
                        'payment_id' => (string) $payment->id,
                        'bill_id' => (string) $payment->bill_id,
                        'status' => Payment::STATUS_RECONCILIATION_PENDING,
                    ]
                );

                if (! ($result['ok'] ?? false)) {
                    Log::warning('Firebase payment notification failed', [
                        'payment_id' => $payment->id,
                        'member_id' => $payment->member_id,
                        'http' => $result['http'] ?? null,
                        'error' => $result['error'] ?? null,
                        'response' => $result['response'] ?? null,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Firebase payment notification exception', [
                    'payment_id' => $payment->id,
                    'member_id' => $payment->member_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function reconcile(PaymentReconciliation $row, int $userId, string $notes = ''): PaymentReconciliation
    {
        return DB::transaction(function () use ($row, $userId, $notes) {
            $lockedRow = PaymentReconciliation::query()->whereKey($row->id)->lockForUpdate()->firstOrFail();
            $before = $lockedRow->toArray();

            $payment = Payment::query()->whereKey($lockedRow->payment_id)->lockForUpdate()->firstOrFail();
            $bill = $this->duplicateGuard->lockBill((int) $payment->bill_id, (int) $payment->member_id);
            $monthCycle = (string) $bill->month_cycle;

            $this->duplicateGuard->assertNoActiveDuplicate((int) $payment->member_id, $monthCycle, (int) $payment->id);
            $this->duplicateGuard->applyGuardAttributes($payment, Payment::STATUS_RECONCILED, $monthCycle)->save();

            $lockedRow->status = Payment::STATUS_RECONCILED;
            $lockedRow->ledger_sync_status = 'SYNCED';
            $lockedRow->accounting_sync_status = 'SYNCED';
            $lockedRow->reconciled_by_user_id = $userId;
            $lockedRow->reconciled_at = now();
            $lockedRow->notes = $notes ?: $lockedRow->notes;
            $lockedRow->save();

            $this->paymentService->transition($payment, Payment::STATUS_RECONCILED, 'reconciliation-completed');
            $this->auditLogService->log('payment.reconciliation_changed', PaymentReconciliation::class, (int) $lockedRow->id, $before, $lockedRow->toArray(), 'reconciled');

            return $lockedRow->fresh();
        });
    }
}
