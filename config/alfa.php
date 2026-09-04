<?php

return [
    'enabled' => env('ALFA_ENABLED', false),
    'env' => env('ALFA_ENV', 'sandbox'),

    // Credentials — server-side only, never exposed to Android/browser
    'merchant_id'       => env('ALFA_MERCHANT_ID'),
    'store_id'          => env('ALFA_STORE_ID'),
    'merchant_username' => env('ALFA_MERCHANT_USERNAME'),
    'merchant_password' => env('ALFA_MERCHANT_PASSWORD'),
    'merchant_hash'     => env('ALFA_MERCHANT_HASH'),
    'key1'              => env('ALFA_ENCRYPTION_KEY_1'),
    'key2'              => env('ALFA_ENCRYPTION_KEY_2'),

    'return_url'   => env('ALFA_RETURN_URL'),
    'listener_url' => env('ALFA_LISTENER_URL'),

    // Pending Bank Alfalah confirmation — no values assumed
    'endpoints'  => [],
    'hash'       => ['algorithm' => null],
    'fees'       => ['gateway_percent' => null, 'fed_percent' => null,
                     'wh_income_tax_percent' => null, 'wh_sales_tax_percent' => null],
    'customer_pays_charges' => false,
];
