<?php

return [
    'default_gateway' => env('PAYMENT_GATEWAY', 'internal_fake'),

    'gateways' => [
        'internal_fake' => [
            'driver' => 'internal_fake',
            'mode' => 'no_live_charging',
        ],
        'jazzcash' => [
            'driver' => 'jazzcash_placeholder',
            'merchant_id' => env('JAZZCASH_MERCHANT_ID', ''),
            'integrity_salt' => env('JAZZCASH_INTEGRITY_SALT', ''),
            'sandbox_only' => true,
        ],
        'easypaisa' => [
            'driver' => 'easypaisa_placeholder',
            'store_id' => env('EASYPAISA_STORE_ID', ''),
            'hash_key' => env('EASYPAISA_HASH_KEY', ''),
            'sandbox_only' => true,
        ],
    ],

    'methods' => [
        'MANUAL_BANK_TRANSFER',
        'CASH',
        'JAZZCASH',
        'EASYPAISA',
        'CARD',
        'OTHER',
    ],
];
