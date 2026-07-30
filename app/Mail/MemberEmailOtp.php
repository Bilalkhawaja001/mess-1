<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MemberEmailOtp extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp,
        public string $memberName = '',
        public int $validMinutes = 10
    ) {}

    public function build()
    {
        return $this->subject('Aap ka verification code')
            ->view('emails.member_email_otp');
    }
}
