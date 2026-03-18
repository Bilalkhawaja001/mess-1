<?php

return [
    'otp' => [
        'length' => 6,
        'ttl_seconds' => 300,
        'resend_cooldown_seconds' => 60,
        'max_verify_attempts' => 5,
        'max_resend_attempts' => 5,
        'driver' => env('MEMBER_OTP_DRIVER', 'log'),
    ],
];
