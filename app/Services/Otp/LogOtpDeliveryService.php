<?php

namespace App\Services\Otp;

use Illuminate\Support\Facades\Log;

class LogOtpDeliveryService implements OtpDeliveryService
{
    public function send(string $mobileNumber, string $otpCode, array $context = []): void
    {
        Log::channel(config('logging.default'))->info('member-registration-otp', [
            'mobile_number' => $mobileNumber,
            'otp_code' => $otpCode,
            'context' => $context,
        ]);
    }
}
