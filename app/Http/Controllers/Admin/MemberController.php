<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Members\StoreMemberRequest;
use App\Http\Requests\Members\UpdateMemberRequest;
use App\Models\Member;
use App\Models\Mess;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(): View
    {
        $rows = Member::query()->with(['user', 'mess'])->orderBy('member_code')->get();
        $users = User::query()->where('is_active', true)->with('role')->orderBy('username')->get();
        $messes = Mess::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.members.index', compact('rows', 'users', 'messes'));
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        Member::query()->create([
            'user_id' => $request->input('user_id') ?: null,
            'member_code' => $request->string('member_code')->toString(),
            'name' => $request->string('name')->toString(),
            'department_name' => $request->input('department_name') ?: null,
            'mess_id' => $request->input('mess_id') ?: null,
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
            'mess_id' => $request->input('mess_id') ?: null,
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

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $counts = ['inserted' => 0, 'updated' => 0, 'failed' => 0];

        foreach ($this->csvRows($request) as $row) {
            $messCode = $this->nullableText($row['mess_code'] ?? null);
            $messId = $this->resolveMessId($messCode);
            $username = $this->nullableText($row['username'] ?? null);
            $userId = $this->resolveUserId($username);

            if ($messCode !== null && $messId === null) {
                $counts['failed']++;
                continue;
            }

            if ($username !== null && $userId === null) {
                $counts['failed']++;
                continue;
            }

            $payload = [
                'member_code' => trim((string) ($row['member_code'] ?? '')),
                'name' => trim((string) ($row['name'] ?? '')),
                'department_name' => $this->nullableText($row['department_name'] ?? null),
                'mess_id' => $messId,
                'user_id' => $userId,
                'mobile_number' => $this->nullableText($row['mobile_number'] ?? null),
                'join_date' => $this->nullableText($row['join_date'] ?? null),
                'leave_date' => $this->nullableText($row['leave_date'] ?? null),
                'is_active' => $this->toBoolean($row['is_active'] ?? true),
            ];

            $validator = Validator::make($payload, [
                'member_code' => 'required|string|max:50',
                'name' => 'required|string|max:120',
                'department_name' => 'nullable|string|max:120',
                'mess_id' => 'nullable|exists:messes,id',
                'user_id' => 'nullable|exists:users,id|unique:members,user_id',
                'mobile_number' => 'nullable|string|max:40',
                'join_date' => 'required|date',
                'leave_date' => 'nullable|date',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                $counts['failed']++;
                continue;
            }

            $existing = Member::query()->where('member_code', $payload['member_code'])->first();
            if ($existing) {
                $existing->update($payload);
                $counts['updated']++;
            } else {
                Member::query()->create($payload);
                $counts['inserted']++;
            }
        }

        return back()->with('success', "Members import done. Inserted: {$counts['inserted']}, Updated: {$counts['updated']}, Failed: {$counts['failed']}");
    }

    public function sampleCsv()
    {
        $headers = ['member_code', 'name', 'department_name', 'mess_code', 'mobile_number', 'join_date', 'leave_date', 'username', 'is_active'];
        $sample = ['M-001', 'Ali Khan', 'Accounts', 'MAIN', '03001234567', now()->toDateString(), '', '', '1'];

        return response()->streamDownload(function () use ($headers, $sample) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, $sample);
            fclose($out);
        }, 'members_sample.csv', ['Content-Type' => 'text/csv']);
    }

    private function csvRows(Request $request): array
    {
        $rows = [];
        $file = fopen($request->file('file')->getRealPath(), 'r');
        $headers = fgetcsv($file) ?: [];
        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headers);

        while (($line = fgetcsv($file)) !== false) {
            if (! array_filter($line, fn ($v) => trim((string) $v) !== '')) {
                continue;
            }
            $rows[] = array_combine($headers, array_pad($line, count($headers), null));
        }

        fclose($file);

        return $rows;
    }

    private function nullableText(mixed $value): ?string
    {
        $v = trim((string) ($value ?? ''));

        return $v === '' ? null : $v;
    }

    private function toBoolean(mixed $value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'y'], true);
    }

    private function resolveMessId(mixed $messCode): ?int
    {
        $code = strtoupper(trim((string) ($messCode ?? '')));

        if ($code === '') {
            return null;
        }

        $mess = Mess::query()->whereRaw('UPPER(code) = ?', [$code])->first();

        return $mess?->id;
    }

    private function resolveUserId(?string $username): ?int
    {
        if ($username === null) {
            return null;
        }

        $user = User::query()->where('username', $username)->first();

        return $user?->id;
    }
}
