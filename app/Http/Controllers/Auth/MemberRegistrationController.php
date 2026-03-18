<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegistrationCompleteRequest;
use App\Http\Requests\Auth\RegistrationStartRequest;
use App\Http\Requests\Auth\RegistrationVerifyOtpRequest;
use App\Models\Member;
use App\Models\MemberRegistrationOtp;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\MemberRegistrationOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MemberRegistrationController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService, private readonly MemberRegistrationOtpService $otpService)
    {
    }

    public function showStart(): View
    {
        return view('auth.member-registration.start');
    }

    public function start(RegistrationStartRequest $request): RedirectResponse
    {
        $memberCode = (string) $request->string('member_id');
        $mobile = trim((string) $request->string('mobile_number'));

        $member = Member::query()->where('member_code', $memberCode)->first();

        $genericError = 'Unable to start registration with provided details.';

        if (! $member || ! $member->is_active || ! $member->portal_enabled || $member->user_id || ($member->mobile_number !== $mobile)) {
            $this->auditLogService->log('member.registration.start.failed', Member::class, $member?->id, [], ['member_code' => $memberCode]);
            return back()->withInput()->with('error', $genericError);
        }

        $otp = $this->otpService->issue($member);

        session([
            'registration.member_id' => $member->id,
            'registration.otp_id' => $otp->id,
        ]);

        return redirect()->route('member.register.verify')->with('success', 'OTP sent to your registered mobile number.');
    }

    public function showVerify(): View|RedirectResponse
    {
        $otp = $this->currentOtp();
        if (! $otp) {
            return redirect()->route('member.register.start')->with('error', 'Registration session expired. Start again.');
        }

        return view('auth.member-registration.verify', [
            'maskedMobile' => $this->otpService->maskMobile($otp->mobile_number),
            'cooldownSeconds' => max(0, (int) config('member_registration.otp.resend_cooldown_seconds', 60) - now()->diffInSeconds($otp->last_sent_at)),
        ]);
    }

    public function verify(RegistrationVerifyOtpRequest $request): RedirectResponse
    {
        $otp = $this->currentOtp();
        if (! $otp) {
            return redirect()->route('member.register.start')->with('error', 'Registration session expired.');
        }

        if ($otp->attempts >= (int) config('member_registration.otp.max_verify_attempts', 5) || $otp->status === 'LOCKED') {
            $this->auditLogService->log('member.registration.otp.verify.failed_locked', Member::class, (int) $otp->member_id);
            return back()->with('error', 'Verification limit exceeded. Please restart registration.');
        }

        if (! $this->otpService->verify($otp, (string) $request->string('otp_code'))) {
            return back()->with('error', 'Invalid or expired OTP.');
        }

        session(['registration.verified_otp_id' => $otp->id]);

        return redirect()->route('member.register.complete');
    }

    public function resend(): RedirectResponse
    {
        $otp = $this->currentOtp();
        if (! $otp) {
            return redirect()->route('member.register.start')->with('error', 'Registration session expired.');
        }

        $cooldown = (int) config('member_registration.otp.resend_cooldown_seconds', 60);
        if (now()->diffInSeconds($otp->last_sent_at) < $cooldown) {
            return back()->with('error', 'Please wait before requesting another OTP.');
        }

        if ($otp->resend_count >= (int) config('member_registration.otp.max_resend_attempts', 5)) {
            $otp->update(['status' => 'LOCKED']);
            $this->auditLogService->log('member.registration.otp.resend.failed_limit', Member::class, (int) $otp->member_id);
            return back()->with('error', 'Resend limit exceeded. Please restart registration.');
        }

        $newOtp = $this->otpService->resend($otp);
        session(['registration.otp_id' => $newOtp->id]);

        return back()->with('success', 'A new OTP has been sent.');
    }

    public function showComplete(): View|RedirectResponse
    {
        $otp = $this->verifiedOtp();
        if (! $otp) {
            return redirect()->route('member.register.start')->with('error', 'Please verify OTP first.');
        }

        return view('auth.member-registration.complete', ['member' => $otp->member]);
    }

    public function complete(RegistrationCompleteRequest $request): RedirectResponse
    {
        $otp = $this->verifiedOtp();
        if (! $otp) {
            return redirect()->route('member.register.start')->with('error', 'Please verify OTP first.');
        }

        $member = $otp->member;
        if ($member->user_id || User::query()->where('member_id', $member->id)->exists()) {
            return redirect()->route('login')->with('error', 'Member portal account already exists.');
        }

        $memberRole = Role::query()->where('code', 'MEMBER')->firstOrFail();
        $email = strtolower((string) $request->string('email'));

        $user = User::query()->create([
            'role_id' => $memberRole->id,
            'member_id' => $member->id,
            'username' => $email,
            'name' => $member->name,
            'email' => $email,
            'password' => (string) $request->string('password'),
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $member->update([
            'user_id' => $user->id,
            'portal_enabled' => true,
            'mobile_verified_at' => $member->mobile_verified_at ?? now(),
            'registered_at' => now(),
        ]);

        $this->auditLogService->log('member.registration.completed', Member::class, (int) $member->id, [], ['user_id' => $user->id]);

        session()->forget(['registration.member_id', 'registration.otp_id', 'registration.verified_otp_id']);

        if (config('auth.member_auto_login', false)) {
            Auth::login($user);
            return redirect()->route('member.dashboard')->with('success', 'Registration completed.');
        }

        return redirect()->route('login')->with('success', 'Registration complete. You can login now with your email as username.');
    }

    private function currentOtp(): ?MemberRegistrationOtp
    {
        $otpId = session('registration.otp_id');
        return $otpId ? MemberRegistrationOtp::query()->with('member')->find($otpId) : null;
    }

    private function verifiedOtp(): ?MemberRegistrationOtp
    {
        $otpId = session('registration.verified_otp_id');
        $otp = $otpId ? MemberRegistrationOtp::query()->with('member')->find($otpId) : null;

        if (! $otp || $otp->status !== 'VERIFIED') {
            return null;
        }

        return $otp;
    }
}
