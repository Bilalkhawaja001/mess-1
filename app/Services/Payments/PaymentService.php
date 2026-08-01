<?php

namespace App\Services\Payments;

use App\Models\Billing;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentService
{
    public function __construct(
        private readonly PaymentStatusTransitionService $statusTransitionService,
        private readonly AuditLogService $auditLogService,
        private readonly PaymentDuplicateGuard $duplicateGuard,
    ) {
    }

    public function createOrGetPending(int $memberId, int $billId, int $methodId, float $amount, int $userId): Payment
    {
        if ($amount <= 0) {
            throw new RuntimeException('Invalid amount. Amount must be greater than zero.');
        }

        return DB::transaction(function () use ($memberId, $billId, $methodId, $amount, $userId) {
            $bill = $this->duplicateGuard->lockBill($billId, $memberId);
            $monthCycle = (string) $bill->month_cycle;

            $this->duplicateGuard->assertNoActiveDuplicate($memberId, $monthCycle, null, $amount);

            $method = PaymentMethod::query()->findOrFail($methodId);
            $payment = Payment::query()->create($this->duplicateGuard->withGuardAttributes([
                'member_id' => $memberId,
                'bill_id' => $bill->id,
                'payment_method_id' => $methodId,
                'payment_ref' => 'PAY-'.now()->format('YmdHis').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
                'payment_date' => now()->toDateString(),
                'amount' => $amount,
                'currency' => 'PKR',
                'method' => $method->code,
                'status' => Payment::STATUS_PENDING,
                'posted_by_user_id' => $userId,
            ], $monthCycle));

            $this->auditLogService->log('payment.created', Payment::class, (int) $payment->id, [], $payment->toArray(), 'payment-root-created');

            return $payment;
        });
    }

    public function transition(Payment $payment, string $toStatus, string $reason = ''): Payment
    {
        $from = (string) $payment->status;
        $this->statusTransitionService->assertTransition($from, $toStatus);

        if (in_array($payment->status, [Payment::STATUS_SUCCESS, Payment::STATUS_RECONCILED], true)
            && in_array($toStatus, [Payment::STATUS_SUCCESS, Payment::STATUS_RECONCILED], true)
        ) {
            throw new RuntimeException('Double-success transition blocked.');
        }

        $before = $payment->toArray();
        $payment->status = $toStatus;
        $payment->save();

        $this->auditLogService->log('payment.status_changed', Payment::class, (int) $payment->id, $before, $payment->toArray(), $reason ?: "{$from}->{$toStatus}");

        return $payment->fresh();
    }
}
