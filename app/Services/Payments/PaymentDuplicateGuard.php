<?php

namespace App\Services\Payments;

use App\Models\Billing;
use App\Models\Payment;
use Illuminate\Database\QueryException;
use RuntimeException;

class DuplicateActivePaymentException extends RuntimeException
{
    /**
     * @param  array<int>  $paymentIds
     */
    public function __construct(private readonly array $paymentIds)
    {
        parent::__construct('Active payment already exists for this member/month. Existing Payment ID: '.$this->firstPaymentId());
    }

    /**
     * @return array<int>
     */
    public function paymentIds(): array
    {
        return $this->paymentIds;
    }

    public function firstPaymentId(): int
    {
        return (int) ($this->paymentIds[0] ?? 0);
    }
}

class PaymentDuplicateGuard
{
    public const UNIQUE_INDEX_NAME = 'uq_payments_active_month_guard_key_v2';

    public const ACTIVE_STATUSES = [
        Payment::STATUS_PENDING,
        Payment::STATUS_INITIATED,
        Payment::STATUS_SUCCESS,
        Payment::STATUS_RECONCILIATION_PENDING,
        Payment::STATUS_APPROVED,
        Payment::STATUS_RECONCILED,
    ];

    public function lockBill(int $billId, int $memberId): Billing
    {
        return Billing::query()
            ->whereKey($billId)
            ->where('member_id', $memberId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @return array<int>
     */
    public function conflictingPaymentIds(int $memberId, string $monthCycle, ?int $excludePaymentId = null, bool $lockForUpdate = true): array
    {
        $query = Payment::query()
            ->join('billings', 'payments.bill_id', '=', 'billings.id')
            ->where('payments.member_id', $memberId)
            ->where('billings.month_cycle', $monthCycle)
            ->whereIn('payments.status', self::ACTIVE_STATUSES)
            ->orderBy('payments.id')
            ->select('payments.id');

        if ($excludePaymentId !== null) {
            $query->where('payments.id', '<>', $excludePaymentId);
        }

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->pluck('payments.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function assertNoActiveDuplicate(int $memberId, string $monthCycle, ?int $excludePaymentId = null): void
    {
        $conflicts = $this->conflictingPaymentIds($memberId, $monthCycle, $excludePaymentId, true);

        if ($conflicts !== []) {
            throw new DuplicateActivePaymentException($conflicts);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function withGuardAttributes(array $attributes, string $monthCycle): array
    {
        $status = (string) ($attributes['status'] ?? '');

        $attributes['month_cycle'] = $monthCycle;
        $attributes['duplicate_guard_version'] = Payment::DUPLICATE_GUARD_VERSION;

        if (in_array($status, self::ACTIVE_STATUSES, true)) {
            $guardKey = self::guardKey((int) $attributes['member_id'], $monthCycle);
            $attributes['active_month_guard_key'] = $guardKey;
            $attributes['active_month_guard_key_v2'] = $guardKey;
        } else {
            $attributes['active_month_guard_key'] = null;
            $attributes['active_month_guard_key_v2'] = null;
        }

        return $attributes;
    }

    public function applyGuardAttributes(Payment $payment, string $status, string $monthCycle): Payment
    {
        $payment->month_cycle = $monthCycle;
        $payment->duplicate_guard_version = Payment::DUPLICATE_GUARD_VERSION;

        if (in_array($status, self::ACTIVE_STATUSES, true)) {
            $guardKey = self::guardKey((int) $payment->member_id, $monthCycle);
            $payment->active_month_guard_key = $guardKey;
            $payment->active_month_guard_key_v2 = $guardKey;
        } else {
            $payment->active_month_guard_key = null;
            $payment->active_month_guard_key_v2 = null;
        }

        return $payment;
    }

    public static function guardKey(int $memberId, string $monthCycle): string
    {
        return $memberId.':'.$monthCycle;
    }

    public static function isGuardUniqueIndexViolation(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), self::UNIQUE_INDEX_NAME);
    }
}
