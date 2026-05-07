<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemberProfileChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MemberProfileChangeRequestController extends Controller
{
    public function index(Request $request): View
    {
        $rows = MemberProfileChangeRequest::query()
            ->with(['member', 'requester', 'approver'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('field_name'), fn ($q) => $q->where('field_name', $request->input('field_name')))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.member-profile-change-requests.index', compact('rows'));
    }

    public function update(Request $request, MemberProfileChangeRequest $changeRequest): RedirectResponse
    {
        $payload = $request->validate([
            'status' => ['required', 'in:APPROVED,REJECTED'],
            'admin_remarks' => ['nullable', 'string'],
        ]);

        if ($changeRequest->status !== MemberProfileChangeRequest::STATUS_PENDING) {
            return back()->with('warning', 'This request has already been processed.');
        }

        DB::transaction(function () use ($changeRequest, $payload) {
            $changeRequest->member()->lockForUpdate()->first();
            $member = $changeRequest->member;
            $user = $member?->user ?? null;

            $changeRequest->status = $payload['status'];
            $changeRequest->admin_remarks = $payload['admin_remarks'] ?? null;
            $changeRequest->approved_by = auth()->id();

            if ($payload['status'] === MemberProfileChangeRequest::STATUS_APPROVED) {
                if ($changeRequest->field_name === MemberProfileChangeRequest::FIELD_EMAIL && $user) {
                    $user->email = $changeRequest->new_value;
                    $user->save();
                }

                if ($changeRequest->field_name === MemberProfileChangeRequest::FIELD_MOBILE && $member) {
                    $member->mobile_number = $changeRequest->new_value;
                    $member->save();
                }

                $changeRequest->approved_at = now();
            }

            $changeRequest->save();
        });

        return back()->with('success', 'Profile change request updated.');
    }
}
