<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\Firebase\FirebaseNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $rows = Announcement::query()
            ->with('sender')
            ->latest('id')
            ->paginate(25);

        return view('admin.announcements.index', compact('rows'));
    }

    public function store(Request $request, FirebaseNotificationService $firebase): RedirectResponse
    {
        $payload = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $announcement = Announcement::query()->create([
            'title' => $payload['title'],
            'message' => $payload['message'],
            'target_type' => 'ALL_MEMBERS',
            'sent_by_user_id' => Auth::id(),
            'sent_at' => now(),
        ]);

        $tokens = DB::table('member_device_tokens')
            ->whereNotNull('device_token')
            ->pluck('device_token')
            ->filter()
            ->unique()
            ->values();

        $success = 0;
        $failed = 0;

        foreach ($tokens as $token) {
            try {
                $result = $firebase->sendToToken(
                    (string) $token,
                    (string) $announcement->title,
                    (string) $announcement->message,
                    [
                        'type' => 'announcement',
                        'announcement_id' => (string) $announcement->id,
                    ]
                );

                if (($result['ok'] ?? false) === true) {
                    $success++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                report($e);
            }
        }

        $announcement->update([
            'total_tokens' => $tokens->count(),
            'success_count' => $success,
            'failed_count' => $failed,
        ]);

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', "Announcement sent. Success: {$success}, Failed: {$failed}");
    }
}
