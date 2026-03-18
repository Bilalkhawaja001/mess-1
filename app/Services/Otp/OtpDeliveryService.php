<?php

namespace App\Services\Otp;

interface OtpDeliveryService
{
    public function send(string $mobileNumber, string $otpCode, array $context = []): void;
}
