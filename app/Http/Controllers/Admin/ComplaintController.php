<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function index(Request $request): View
    {
        $rows = Complaint::query()
            ->with(['user', 'assignee'])
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->input('priority')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->input('category')))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        $assignableUsers = User::query()->with('role')->orderBy('name')->get();

        return view('admin.complaints.index', compact('rows', 'assignableUsers'));
    }

    public function show(Complaint $complaint): View
    {
        $complaint->load(['user', 'assignee']);

        return view('admin.complaints.show', compact('complaint'));
    }

    public function updateStatus(Request $request, Complaint $complaint): RedirectResponse
    {
        $payload = $request->validate([
            'status' => ['required', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'admin_remarks' => ['nullable', 'string'],
        ]);

        $nextStatus = (string) $payload['status'];
        $isCloseAction = in_array($nextStatus, [Complaint::STATUS_CLOSED, Complaint::STATUS_REJECTED], true);
        if ($isCloseAction) {
            abort_unless(auth()->user()->hasPermission('complaint.close'), 403);
        }

        $complaint->status = $nextStatus;
        $complaint->assigned_to = $payload['assigned_to'] ?? null;
        $complaint->admin_remarks = $payload['admin_remarks'] ?? null;

        if ($nextStatus === Complaint::STATUS_RESOLVED && ! $complaint->resolved_at) {
            $complaint->resolved_at = now();
        }
        if ($nextStatus === Complaint::STATUS_CLOSED && ! $complaint->closed_at) {
            $complaint->closed_at = now();
        }
        if ($nextStatus !== Complaint::STATUS_RESOLVED) {
            $complaint->resolved_at = $nextStatus === Complaint::STATUS_CLOSED ? ($complaint->resolved_at ?? now()) : $complaint->resolved_at;
        }

        $complaint->save();

        return back()->with('success', 'Complaint updated.');
    }

    public function export(Request $request)
    {
        $rows = Complaint::query()
            ->with(['user', 'assignee'])
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->input('priority')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->input('category')))
            ->orderByDesc('id')
            ->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Complaint No', 'Date', 'Submitted By', 'Type', 'Category', 'Subject', 'Priority', 'Status', 'Assigned To', 'Resolved At', 'Closed At']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->complaint_no,
                    optional($row->created_at)->format('Y-m-d H:i'),
                    $row->user?->name ?? $row->submitted_by_name,
                    $row->type,
                    $row->category,
                    $row->subject,
                    $row->priority,
                    $row->status,
                    $row->assignee?->name,
                    optional($row->resolved_at)->format('Y-m-d H:i'),
                    optional($row->closed_at)->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, 'complaints.csv', ['Content-Type' => 'text/csv']);
    }
}
