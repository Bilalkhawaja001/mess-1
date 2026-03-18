<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\PaymentReconciliation;
use App\Models\PaymentTransaction;
use App\Services\AuditLogService;

class PaymentReconciliationService
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly AuditLogService $auditLogService,
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

        return $row;
    }

    public function reconcile(PaymentReconciliation $row, int $userId, string $notes = ''): PaymentReconciliation
    {
        $before = $row->toArray();
        $row->status = Payment::STATUS_RECONCILED;
        $row->ledger_sync_status = 'SYNCED';
        $row->accounting_sync_status = 'SYNCED';
        $row->reconciled_by_user_id = $userId;
        $row->reconciled_at = now();
        $row->notes = $notes ?: $row->notes;
        $row->save();

        $this->paymentService->transition($row->payment, Payment::STATUS_RECONCILED, 'reconciliation-completed');
        $this->auditLogService->log('payment.reconciliation_changed', PaymentReconciliation::class, (int) $row->id, $before, $row->toArray(), 'reconciled');

        return $row->fresh();
    }
}
