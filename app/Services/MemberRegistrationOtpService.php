<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberRegistrationOtp;
use App\Services\Otp\OtpDeliveryService;
use Illuminate\Support\Facades\Hash;

class MemberRegistrationOtpService
{
    public function __construct(
        private readonly OtpDeliveryService $deliveryService,
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function issue(Member $member, bool $isResend = false): MemberRegistrationOtp
    {
        $this->invalidateActiveOtps($member, $isResend ? 'SUPERSEDED_RESEND' : 'SUPERSEDED');

        $otp = str_pad((string) random_int(0, 999999), (int) config('member_registration.otp.length', 6), '0', STR_PAD_LEFT);

        $record = MemberRegistrationOtp::query()->create([
            'member_id' => $member->id,
            'mobile_number' => (string) $member->mobile_number,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addSeconds((int) config('member_registration.otp.ttl_seconds', 300)),
            'attempts' => 0,
            'resend_count' => $isResend ? 1 : 0,
            'last_sent_at' => now(),
            'status' => 'SENT',
            'ip_address' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 255),
        ]);

        $this->deliveryService->send((string) $member->mobile_number, $otp, ['member_id' => $member->id]);

        $this->auditLogService->log(
            $isResend ? 'member.registration.otp.resent' : 'member.registration.otp.sent',
            Member::class,
            (int) $member->id,
            [],
            ['mobile_masked' => $this->maskMobile((string) $member->mobile_number)]
        );

        return $record;
    }

    public function resend(MemberRegistrationOtp $otp): MemberRegistrationOtp
    {
        $otp->update(['status' => 'SUPERSEDED_RESEND']);
        $member = $otp->member;

        $new = $this->issue($member, true);
        $new->resend_count = $otp->resend_count + 1;
        $new->save();

        return $new;
    }

    public function verify(MemberRegistrationOtp $otp, string $plainOtp): bool
    {
        if ($otp->status !== 'SENT') {
            return false;
        }

        if ($otp->expires_at->isPast()) {
            $otp->update(['status' => 'EXPIRED']);
            $this->auditLogService->log('member.registration.otp.verify.failed_expired', Member::class, (int) $otp->member_id);
            return false;
        }

        $otp->increment('attempts');

        if (! Hash::check($plainOtp, $otp->otp_hash)) {
            if ($otp->attempts >= (int) config('member_registration.otp.max_verify_attempts', 5)) {
                $otp->update(['status' => 'LOCKED']);
            }
            $this->auditLogService->log('member.registration.otp.verify.failed_wrong', Member::class, (int) $otp->member_id);
            return false;
        }

        $otp->update([
            'status' => 'VERIFIED',
            'verified_at' => now(),
        ]);

        $this->auditLogService->log('member.registration.otp.verify.success', Member::class, (int) $otp->member_id);
        return true;
    }

    public function invalidateActiveOtps(Member $member, string $status = 'SUPERSEDED'): void
    {
        MemberRegistrationOtp::query()
            ->where('member_id', $member->id)
            ->where('status', 'SENT')
            ->update(['status' => $status]);
    }

    public function maskMobile(string $mobile): string
    {
        $len = strlen($mobile);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }
        return str_repeat('*', $len - 4).substr($mobile, -4);
    }
}
