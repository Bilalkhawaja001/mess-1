<?php

namespace App\Services\Payments;

use App\Models\MemberLedger;
use App\Models\Payment;
use App\Models\PaymentReconciliation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UploadedProofService
{
    public function __construct(private PaymentDuplicateGuard $duplicateGuard)
    {
    }

    /**
     * @throws \RuntimeException when the proof file is missing or fails integrity check
     */
    public function assertProofIntact(Payment $payment): void
    {
        $proofRow = $payment->reconciliations()->latest('id')->first();
        $meta = $proofRow?->meta ?? [];

        if (! is_array($meta)) {
            $meta = json_decode((string) $meta, true) ?: [];
        }

        $path = (string) ($meta['screenshot_path'] ?? '');
        $disk = (string) ($meta['screenshot_disk'] ?? 'local');
        $expectedHash = (string) ($meta['screenshot_sha256'] ?? '');

        if ($path === '' || ! in_array($disk, ['local', 'public'], true) || ! Storage::disk($disk)->exists($path)) {
            throw new \RuntimeException('Payment proof file is missing. Cannot approve.');
        }

        if ($expectedHash !== '') {
            $actualHash = hash_file('sha256', Storage::disk($disk)->path($path));
            if (! hash_equals($expectedHash, $actualHash)) {
                throw new \RuntimeException('Payment proof file integrity check failed. Cannot approve.');
            }
        }
    }

    public function approve(Payment $payment, int $userId, string $note = 'Android payment proof approved from admin payments screen.'): void
    {
        $this->assertProofIntact($payment);

        DB::transaction(function () use ($payment, $userId, $note) {
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $bill = $this->duplicateGuard->lockBill((int) $lockedPayment->bill_id, (int) $lockedPayment->member_id);
            $monthCycle = (string) $bill->month_cycle;

            $this->duplicateGuard->assertNoActiveDuplicate(
                (int) $lockedPayment->member_id,
                $monthCycle,
                (int) $lockedPayment->id,
                (float) $lockedPayment->amount
            );
            $this->duplicateGuard->applyGuardAttributes($lockedPayment, Payment::STATUS_RECONCILED, $monthCycle);

            $existingLedger = MemberLedger::query()
                ->where('member_id', $lockedPayment->member_id)
                ->where('ref_type', 'PAYMENT')
                ->where('ref_id', $lockedPayment->id)
                ->first();

            if (! $existingLedger) {
                $lastBal = (float) (MemberLedger::query()
                    ->where('member_id', $lockedPayment->member_id)
                    ->orderByDesc('entry_date')
                    ->orderByDesc('id')
                    ->value('balance_after') ?? 0);

                $newBal = round($lastBal - (float) $lockedPayment->amount, 2);

                MemberLedger::query()->create([
                    'member_id' => $lockedPayment->member_id,
                    'entry_date' => $lockedPayment->payment_date,
                    'debit' => 0,
                    'credit' => $lockedPayment->amount,
                    'ref_type' => 'PAYMENT',
                    'ref_id' => $lockedPayment->id,
                    'balance_after' => $newBal,
                    'reason_code' => 'ANDROID_PAYMENT_PROOF_APPROVED',
                    'posted_by_user_id' => $userId,
                ]);
            }

            $lockedPayment->status = Payment::STATUS_RECONCILED;
            $lockedPayment->approved_by_user_id = $userId;
            $lockedPayment->approved_at = now();
            $lockedPayment->save();

            PaymentReconciliation::query()
                ->where('payment_id', $payment->id)
                ->update([
                    'status' => Payment::STATUS_RECONCILED,
                    'ledger_sync_status' => 'SYNCED',
                    'accounting_sync_status' => 'SYNCED',
                    'reconciled_by_user_id' => $userId,
                    'reconciled_at' => now(),
                    'notes' => $note,
                    'updated_at' => now(),
                ]);
        });
    }

    public function reject(Payment $payment, ?string $reason, string $note = 'Android payment proof rejected from admin payments screen.'): void
    {
        DB::transaction(function () use ($payment, $reason, $note) {
            $payment->status = Payment::STATUS_FAILED;
            $payment->notes = trim(($payment->notes ? $payment->notes.PHP_EOL : '').'Rejected: '.($reason ?? 'Payment proof rejected by admin.'));
            $payment->save();

            PaymentReconciliation::query()
                ->where('payment_id', $payment->id)
                ->update([
                    'status' => Payment::STATUS_FAILED,
                    'ledger_sync_status' => 'REJECTED',
                    'accounting_sync_status' => 'REJECTED',
                    'mismatch_reason' => $reason ?? 'Rejected by admin',
                    'notes' => $note,
                    'updated_at' => now(),
                ]);
        });
    }
}
