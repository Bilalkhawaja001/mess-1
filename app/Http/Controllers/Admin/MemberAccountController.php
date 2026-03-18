<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateMemberAccountRequest;
use App\Http\Requests\Admin\MemberLifecycleActionRequest;
use App\Models\Member;
use App\Models\MemberRegistrationOtp;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MemberAccountController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function index(): View
    {
        $members = Member::query()->with('user')->orderBy('member_code')->get();
        return view('admin.member-accounts.index', compact('members'));
    }

    public function store(CreateMemberAccountRequest $request): RedirectResponse
    {
        $member = Member::query()->findOrFail($request->integer('member_id'));
        if ($member->user_id) {
            return back()->with('error', 'Member already has a portal account.');
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
            'must_change_password' => (bool) $request->boolean('force_password_change'),
        ]);

        $member->update([
            'user_id' => $user->id,
            'portal_enabled' => true,
            'mobile_verified_at' => $request->boolean('mark_mobile_verified') ? now() : $member->mobile_verified_at,
            'registered_at' => $member->registered_at ?? now(),
        ]);

        $this->auditLogService->log('admin.member_account.created', Member::class, (int) $member->id, [], [
            'user_id' => $user->id,
            'force_password_change' => (bool) $request->boolean('force_password_change'),
            'mobile_marked_verified' => (bool) $request->boolean('mark_mobile_verified'),
        ]);

        return back()->with('success', 'Member portal account created.');
    }

    public function activate(Member $member): RedirectResponse
    {
        if (! $member->user) {
            return back()->with('error', 'No account linked with this member.');
        }

        $member->user->update(['is_active' => true]);
        $member->update(['portal_enabled' => true]);
        $this->auditLogService->log('admin.member_account.activated', Member::class, (int) $member->id);

        return back()->with('success', 'Member portal activated.');
    }

    public function deactivate(Member $member): RedirectResponse
    {
        if (! $member->user) {
            return back()->with('error', 'No account linked with this member.');
        }

        $member->user->update(['is_active' => false]);
        $member->update(['portal_enabled' => false]);
        $this->auditLogService->log('admin.member_account.deactivated', Member::class, (int) $member->id);

        return back()->with('success', 'Member portal deactivated.');
    }

    public function reset(MemberLifecycleActionRequest $request, Member $member): RedirectResponse
    {
        if (! $member->user) {
            return back()->with('error', 'No account linked with this member.');
        }

        $tempPassword = Str::password(12);
        $member->user->update([
            'password' => $tempPassword,
            'is_active' => true,
            'must_change_password' => true,
        ]);

        $this->auditLogService->log('admin.member_account.reset', Member::class, (int) $member->id, [], ['temporary_password_issued' => true]);

        return back()->with('success', 'Member access reset. Temporary password issued: '.$tempPassword);
    }

    public function unlockOtp(Member $member): RedirectResponse
    {
        MemberRegistrationOtp::query()
            ->where('member_id', $member->id)
            ->whereIn('status', ['LOCKED', 'EXPIRED', 'SENT'])
            ->update(['status' => 'UNLOCKED']);

        $this->auditLogService->log('admin.member_account.otp_unlock', Member::class, (int) $member->id);
        return back()->with('success', 'Member OTP/registration lock cleared.');
    }

    public function markMobileVerified(Member $member): RedirectResponse
    {
        $member->update(['mobile_verified_at' => now()]);
        $this->auditLogService->log('admin.member_account.mobile_verified_marked', Member::class, (int) $member->id);

        return back()->with('success', 'Member mobile marked verified.');
    }
}
