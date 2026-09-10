<?php

namespace App\Services\Payments;

use App\Models\MemberLedger;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;

class ManualPaymentService
{
    public function __construct(private PaymentDuplicateGuard $duplicateGuard)
    {
    }

    /**
     * @param  array{payment_date?:string|null, reference_no?:string|null, notes?:string|null}  $extra
     */
    public function post(int $memberId, int $billId, int $methodId, float $amount, int $userId, array $extra = []): Payment
    {
        $amount = round($amount, 2);

        return DB::transaction(function () use ($memberId, $billId, $methodId, $amount, $userId, $extra) {
            $bill = $this->duplicateGuard->lockBill($billId, $memberId);
            $monthCycle = (string) $bill->month_cycle;

            $this->duplicateGuard->assertNoActiveDuplicate($memberId, $monthCycle, null, $amount);

            $method = PaymentMethod::query()
                ->whereKey($methodId)
                ->where('is_active', true)
                ->firstOrFail();

            $payment = Payment::query()->create($this->duplicateGuard->withGuardAttributes([
                'member_id' => $memberId,
                'bill_id' => $bill->id,
                'payment_method_id' => $method->id,
                'payment_ref' => 'MANPAY-'.now()->format('YmdHis').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
                'payment_date' => $extra['payment_date'] ?? now()->toDateString(),
                'amount' => $amount,
                'currency' => 'PKR',
                'method' => $method->code,
                'reference_no' => $extra['reference_no'] ?? null,
                'notes' => $extra['notes'] ?? null,
                'status' => Payment::STATUS_APPROVED,
                'posted_by_user_id' => $userId,
                'approved_by_user_id' => $userId,
                'approved_at' => now(),
            ], $monthCycle));

            $exists = MemberLedger::query()
                ->where('member_id', $memberId)
                ->where('ref_type', 'PAYMENT')
                ->where('ref_id', $payment->id)
                ->exists();

            if (! $exists) {
                $lastBal = (float) (MemberLedger::query()
                    ->where('member_id', $memberId)
                    ->orderByDesc('entry_date')
                    ->orderByDesc('id')
                    ->value('balance_after') ?? 0);

                MemberLedger::query()->create([
                    'member_id' => $memberId,
                    'entry_date' => $payment->payment_date,
                    'debit' => 0,
                    'credit' => $amount,
                    'ref_type' => 'PAYMENT',
                    'ref_id' => $payment->id,
                    'balance_after' => round($lastBal - $amount, 2),
                    'reason_code' => 'PAYMENT_APPROVAL',
                    'posted_by_user_id' => $userId,
                ]);
            }

            return $payment;
        });
    }
}
