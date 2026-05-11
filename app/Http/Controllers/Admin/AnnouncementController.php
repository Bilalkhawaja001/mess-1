<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\Firebase\FirebaseNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $invalidTokens = [];

        foreach ($tokens as $token) {
            $token = (string) $token;

            try {
                $result = $firebase->sendToToken(
                    $token,
                    (string) $announcement->title,
                    (string) $announcement->message,
                    [
                        'type' => 'announcement',
                        'announcement_id' => (string) $announcement->id,
                    ]
                );

                if (($result['ok'] ?? false) === true) {
                    $success++;
                    continue;
                }

                $failed++;

                Log::warning('ANNOUNCEMENT_FCM_FAILED', [
                    'announcement_id' => $announcement->id,
                    'token_tail' => substr($token, -12),
                    'result' => $result,
                ]);

                if ($this->isInvalidFirebaseTokenResponse($result)) {
                    $invalidTokens[] = $token;
                }
            } catch (\Throwable $e) {
                $failed++;

                Log::warning('ANNOUNCEMENT_FCM_EXCEPTION', [
                    'announcement_id' => $announcement->id,
                    'token_tail' => substr($token, -12),
                    'message' => $e->getMessage(),
                ]);

                if ($this->isInvalidFirebaseTokenMessage($e->getMessage())) {
                    $invalidTokens[] = $token;
                }

                report($e);
            }
        }

        $invalidTokens = array_values(array_unique($invalidTokens));

        if (! empty($invalidTokens)) {
            DB::table('member_device_tokens')
                ->whereIn('device_token', $invalidTokens)
                ->delete();

            Log::info('ANNOUNCEMENT_FCM_INVALID_TOKENS_DELETED', [
                'announcement_id' => $announcement->id,
                'deleted_count' => count($invalidTokens),
            ]);
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

    private function isInvalidFirebaseTokenResponse(array $result): bool
    {
        return $this->isInvalidFirebaseTokenMessage(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
    }

    private function isInvalidFirebaseTokenMessage(string $message): bool
    {
        $message = strtoupper($message);

        return str_contains($message, 'UNREGISTERED')
            || str_contains($message, 'NOT_FOUND')
            || str_contains($message, 'INVALID_ARGUMENT')
            || str_contains($message, 'REGISTRATION_TOKEN_NOT_REGISTERED')
            || str_contains($message, 'NOT REGISTERED')
            || str_contains($message, 'INVALID REGISTRATION');
    }

}
