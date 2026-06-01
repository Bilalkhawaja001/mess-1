<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $member = Auth::user()?->resolvedMemberProfile();

        if (! $member) {
            return redirect()->route('member.dashboard')->with('warning', 'Your member profile is not linked yet. Please contact admin.');
        }

        $rows = Complaint::query()->where('member_id', $member->id)->latest('id')->paginate(25);

        return view('member.mobile.complaints.index', compact('rows'));
    }

    public function create(): View|RedirectResponse
    {
        if (! Auth::user()?->resolvedMemberProfile()) {
            return redirect()->route('member.dashboard')->with('warning', 'Your member profile is not linked yet. Please contact admin.');
        }

        return view('member.mobile.complaints.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'type' => ['required', 'in:COMPLAINT,SUGGESTION,MAINTENANCE_REQUEST'],
            'category' => ['required', 'in:FOOD_QUALITY,FOOD_QUANTITY,CLEANLINESS,STAFF_BEHAVIOR,MENU_ISSUE,PAYMENT_BILL_ISSUE,WATER_ISSUE,OTHER'],
            'priority' => ['required', 'in:LOW,NORMAL,HIGH,URGENT'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = Auth::user();
        $member = $user?->resolvedMemberProfile();

        if (! $member) {
            return redirect()->route('member.dashboard')->with('warning', 'Your member profile is not linked yet. Please contact admin.');
        }

        $complaint = Complaint::query()->create([
            'complaint_no' => 'CMP-' . now()->format('Ymd') . '-' . str_pad((string) (Complaint::query()->count() + 1), 5, '0', STR_PAD_LEFT),
            'user_id' => $user->id,
            'member_id' => $member->id,
            'submitted_by_name' => $user->name,
            'type' => $payload['type'],
            'category' => $payload['category'],
            'subject' => $payload['subject'],
            'description' => $payload['message'],
            'message' => $payload['message'],
            'priority' => $payload['priority'],
            'status' => Complaint::STATUS_PENDING,
        ]);

        foreach ($request->file('attachments', []) as $attachment) {
            $storedPath = $attachment->store('complaint-attachments', 'public');

            $complaint->attachments()->create([
                'uploaded_by_user_id' => $user->id,
                'disk' => 'public',
                'path' => $storedPath,
                'original_name' => $attachment->getClientOriginalName(),
                'mime_type' => $attachment->getClientMimeType(),
                'size_bytes' => $attachment->getSize(),
            ]);
        }

        return redirect()
            ->route($request->routeIs('member.app.*') ? 'member.app.complaints.index' : 'member.complaints.index')
            ->with('success', 'Complaint / suggestion submitted successfully.');
    }

    public function show(Complaint $complaint): View|RedirectResponse
    {
        $member = Auth::user()?->resolvedMemberProfile();

        if (! $member) {
            return redirect()->route('member.dashboard')->with('warning', 'Your member profile is not linked yet. Please contact admin.');
        }

        abort_unless((int) $complaint->member_id === (int) $member->id, 403);

        $complaint->load('attachments');

        return view(request()->routeIs('member.app.*') ? 'member.app.complaints.show' : 'member.complaints.show', compact('complaint'));
    }
}
