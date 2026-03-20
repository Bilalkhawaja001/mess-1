<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Members\StoreMemberRequest;
use App\Http\Requests\Members\UpdateMemberRequest;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(): View
    {
        $rows = Member::query()->with('user')->orderBy('member_code')->get();
        $users = User::query()->where('is_active', true)->with('role')->orderBy('username')->get();

        return view('admin.members.index', compact('rows', 'users'));
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        Member::query()->create([
            'user_id' => $request->input('user_id') ?: null,
            'member_code' => $request->string('member_code')->toString(),
            'name' => $request->string('name')->toString(),
            'department_name' => $request->input('department_name') ?: null,
            'mobile_number' => $request->input('mobile_number') ?: null,
            'join_date' => $request->input('join_date'),
            'leave_date' => $request->input('leave_date') ?: null,
            'is_active' => (bool) $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.members.index')->with('success', 'Member created.');
    }

    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        $member->update([
            'user_id' => $request->input('user_id') ?: null,
            'member_code' => $request->string('member_code')->toString(),
            'name' => $request->string('name')->toString(),
            'department_name' => $request->input('department_name') ?: null,
            'mobile_number' => $request->input('mobile_number') ?: null,
            'join_date' => $request->input('join_date'),
            'leave_date' => $request->input('leave_date') ?: null,
            'is_active' => (bool) $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.members.index')->with('success', 'Member updated.');
    }

    public function toggleActive(Member $member): RedirectResponse
    {
        $member->is_active = ! $member->is_active;
        if (! $member->is_active && ! $member->leave_date) {
            $member->leave_date = now()->toDateString();
        }
        if ($member->is_active) {
            $member->leave_date = null;
        }
        $member->save();

        return redirect()->route('admin.members.index')->with('success', 'Member status updated.');
    }

    public function deactivate(Member $member): RedirectResponse
    {
        if ($member->is_active) {
            $member->is_active = false;
            $member->leave_date = $member->leave_date ?: now()->toDateString();
            $member->save();
        }

        return redirect()->route('admin.members.index')->with('success', 'Member deactivated.');
    }

    public function reactivate(Member $member): RedirectResponse
    {
        if (! $member->is_active) {
            $member->is_active = true;
            $member->leave_date = null;
            $member->save();
        }

        return redirect()->route('admin.members.index')->with('success', 'Member reactivated.');
    }

    public function remove(Member $member): RedirectResponse
    {
        $member->delete();

        return redirect()->route('admin.members.index')->with('success', 'Member removed.');
    }
}
