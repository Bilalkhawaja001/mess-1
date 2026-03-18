<?php

namespace App\Services\Payments\Gateways;

use App\Services\Payments\PaymentGatewayInterface;

class InternalFakeGatewayAdapter implements PaymentGatewayInterface
{
    public function initiate(array $payload): array
    {
        return [
            'ok' => true,
            'mode' => 'internal_fake',
            'external_ref' => null,
            'merchant_ref' => $payload['merchant_ref'] ?? null,
            'message' => 'No live charging. Internal simulation only.',
        ];
    }

    public function verify(array $payload): array
    {
        return ['ok' => true, 'verified' => true, 'mode' => 'internal_fake'];
    }

    public function refund(array $payload): array
    {
        return ['ok' => true, 'refunded' => true, 'mode' => 'internal_fake'];
    }
}
