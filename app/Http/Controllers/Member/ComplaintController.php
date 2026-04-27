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
    public function index(): View
    {
        $rows = Complaint::query()->where('user_id', Auth::id())->latest('id')->paginate(25);

        return view('member.complaints.index', compact('rows'));
    }

    public function create(): View
    {
        return view('member.complaints.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'type' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:120'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['nullable', 'string'],
            'submitted_by_contact' => ['nullable', 'string', 'max:120'],
        ]);

        $user = Auth::user();

        Complaint::query()->create([
            'complaint_no' => 'CMP-' . now()->format('Ymd') . '-' . str_pad((string) (Complaint::query()->count() + 1), 5, '0', STR_PAD_LEFT),
            'user_id' => $user->id,
            'submitted_by_name' => $user->name,
            'submitted_by_contact' => $payload['submitted_by_contact'] ?? null,
            'type' => $payload['type'],
            'category' => $payload['category'] ?? null,
            'subject' => $payload['subject'],
            'description' => $payload['description'],
            'priority' => $payload['priority'] ?? Complaint::PRIORITY_NORMAL,
            'status' => Complaint::STATUS_OPEN,
        ]);

        return redirect()->route('member.complaints.index')->with('success', 'Complaint submitted successfully.');
    }

    public function show(Complaint $complaint): View
    {
        abort_unless($complaint->user_id === Auth::id(), 403);

        return view('member.complaints.show', compact('complaint'));
    }
}
