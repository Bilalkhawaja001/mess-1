<?php
namespace App\Services\Payments\Gateways;

use App\Services\Payments\PaymentGatewayInterface;
use RuntimeException;

/**
 * Bank Alfalah (APG) adapter — NOT OPERATIONAL.
 *
 * Deliberately does NOT extend InternalFakeGatewayAdapter: that adapter
 * returns verified=true unconditionally, which for a real gateway would
 * allow a payment to settle without any bank confirmation.
 *
 * Pending Bank Alfalah confirmation:
 *   - API endpoints, RequestHash algorithm, Key1/Key2 format
 *   - fee / FED / withholding calculation
 *   - refund rules
 */
class AlfaGatewayAdapter implements PaymentGatewayInterface
{
    private function notConfigured(string $op): never
    {
        throw new RuntimeException("Alfa gateway not configured: {$op} unavailable (pending Bank Alfalah approval).");
    }

    public function initiate(array $payload): array
    {
        if (! config('alfa.enabled')) {
            return ['ok' => false, 'mode' => 'alfa', 'external_ref' => null,
                    'merchant_ref' => $payload['merchant_ref'] ?? null,
                    'message' => 'Online payment is being activated.'];
        }
        $this->notConfigured('initiate');
    }

    public function verify(array $payload): array
    {
        // Never returns verified=true without a server-to-server bank confirmation.
        return ['ok' => false, 'verified' => false, 'mode' => 'alfa',
                'message' => 'Alfa verification not implemented.'];
    }

    public function refund(array $payload): array
    {
        $this->notConfigured('refund');
    }
}
