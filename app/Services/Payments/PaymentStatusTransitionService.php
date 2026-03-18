<?php

namespace App\Services\Payments;

use InvalidArgumentException;

class PaymentStatusTransitionService
{
    public function assertTransition(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }

        $allowed = [
            'PENDING' => ['INITIATED', 'CANCELLED', 'EXPIRED', 'FAILED'],
            'INITIATED' => ['SUCCESS', 'FAILED', 'CANCELLED', 'EXPIRED', 'RECONCILIATION_PENDING'],
            'SUCCESS' => ['RECONCILIATION_PENDING', 'RECONCILED', 'REFUNDED', 'REVERSED'],
            'FAILED' => ['INITIATED'],
            'CANCELLED' => [],
            'EXPIRED' => ['INITIATED'],
            'REFUNDED' => [],
            'REVERSED' => [],
            'RECONCILIATION_PENDING' => ['RECONCILED', 'FAILED'],
            'RECONCILED' => ['REFUNDED', 'REVERSED'],
            // Legacy states (compat)
            'DRAFT' => ['PENDING', 'INITIATED', 'SUCCESS', 'FAILED', 'CANCELLED'],
            'APPROVED' => ['SUCCESS', 'RECONCILIATION_PENDING', 'RECONCILED'],
        ];

        $fromAllowed = $allowed[$from] ?? [];
        if (! in_array($to, $fromAllowed, true)) {
            throw new InvalidArgumentException("Invalid payment status transition: {$from} -> {$to}");
        }
    }
}
