<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MemberProfileChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = Auth::user();
        $member = $user?->resolvedMemberProfile();

        if (! $member) {
            return redirect()->route('member.dashboard')->with('warning', 'Your member profile is not linked yet. Please contact admin.');
        }

        $changeRequests = MemberProfileChangeRequest::query()
            ->where('member_id', $member->id)
            ->latest('id')
            ->limit(20)
            ->get();

        return view('member.mobile.profile', [
            'user' => $user,
            'member' => $member,
            'changeRequests' => $changeRequests,
        ]);
    }

    public function storeChangeRequest(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'field_name' => ['required', 'in:email,mobile'],
            'new_value' => ['required', 'string', 'max:255'],
        ]);

        $user = Auth::user();
        $member = $user?->resolvedMemberProfile();

        if (! $member) {
            return redirect()->route('member.dashboard')->with('warning', 'Your member profile is not linked yet. Please contact admin.');
        }

        $fieldName = (string) $payload['field_name'];
        $oldValue = $fieldName === 'email'
            ? ($user?->email ?? null)
            : ($member->mobile_number ?? null);

        MemberProfileChangeRequest::query()->create([
            'member_id' => $member->id,
            'requested_by_user_id' => $user?->id,
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => trim((string) $payload['new_value']),
            'status' => MemberProfileChangeRequest::STATUS_PENDING,
        ]);

        return redirect()
            ->route($request->routeIs('member.app.*') ? 'member.app.profile.index' : 'member.profile.index')
            ->with('success', 'Profile change request submitted for approval.');
    }
}
