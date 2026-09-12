<?php

namespace App\Http\Controllers\Api\Kitchen;

use App\Models\Member;
use App\Models\MemberLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KitchenOutstandingController extends KitchenAuthController
{
    public function lookup(Request $request): JsonResponse
    {
        $staff = $this->staff($request);
        if (! $staff) {
            return $this->unauthenticated();
        }

        if (! $staff->can_view_outstanding) {
            return response()->json(['success' => false, 'message' => 'Not allowed'], 403);
        }

        $data = $request->validate([
            'member_code' => ['required', 'string', 'max:50'],
        ]);

        $member = Member::query()
            ->where('member_code', trim($data['member_code']))
            ->first(['id', 'member_code', 'name', 'department_name']);

        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $outstanding = (float) (MemberLedger::query()
            ->where('member_id', $member->id)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->value('balance_after') ?? 0);

        return response()->json([
            'success' => true,
            'member_code' => $member->member_code,
            'member_name' => $member->name,
            'department' => $member->department_name,
            'outstanding' => number_format($outstanding, 2, '.', ''),
        ]);
    }
}
