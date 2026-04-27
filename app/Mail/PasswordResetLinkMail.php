<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $resetUrl,
        public readonly int $expiryMinutes,
    ) {
    }

    public function build(): self
    {
        return $this->subject('Reset your Mess Billing password')
            ->view('emails.auth.password_reset_link');
    }
}
