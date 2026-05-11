<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        return view('member.complaints.index', compact('rows'));
    }

    public function create(): View|RedirectResponse
    {
        if (! Auth::user()?->resolvedMemberProfile()) {
            return redirect()->route('member.dashboard')->with('warning', 'Your member profile is not linked yet. Please contact admin.');
        }

        return view('member.complaints.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'type' => ['required', 'in:COMPLAINT,SUGGESTION,MAINTENANCE_REQUEST'],
            'category' => ['required', 'in:FOOD_QUALITY,FOOD_QUANTITY,CLEANLINESS,STAFF_BEHAVIOR,MENU_ISSUE,PAYMENT_BILL_ISSUE,WATER_ISSUE,OTHER'],
            'priority' => ['required', 'in:LOW,NORMAL,HIGH,URGENT'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $member = $user?->resolvedMemberProfile();

        if (! $member) {
            return redirect()->route('member.dashboard')->with('warning', 'Your member profile is not linked yet. Please contact admin.');
        }

        Complaint::query()->create([
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

        return redirect()->route('member.complaints.index')->with('success', 'Complaint / suggestion submitted successfully.');
    }

    public function show(Complaint $complaint): View|RedirectResponse
    {
        $member = Auth::user()?->resolvedMemberProfile();

        if (! $member) {
            return redirect()->route('member.dashboard')->with('warning', 'Your member profile is not linked yet. Please contact admin.');
        }

        abort_unless((int) $complaint->member_id === (int) $member->id, 403);

        return view('member.complaints.show', compact('complaint'));
    }
}
