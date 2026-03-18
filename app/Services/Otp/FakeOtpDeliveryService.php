<?php

namespace App\Services\Otp;

use Illuminate\Support\Facades\Log;

class FakeOtpDeliveryService implements OtpDeliveryService
{
    public function send(string $mobileNumber, string $otpCode, array $context = []): void
    {
        Log::debug('fake-member-registration-otp', [
            'mobile_number' => $mobileNumber,
            'otp_code' => $otpCode,
            'context' => $context,
        ]);
    }
}
