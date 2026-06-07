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
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $rows = Announcement::query()
            ->with('sender')
            ->latest('id')
            ->paginate(25);

        $memberOptions = DB::table('members')
            ->select('id', 'member_code', 'name', 'department_name')
            ->where('is_active', true)
            ->orderBy('member_code')
            ->limit(1500)
            ->get();

        return view('admin.announcements.index', compact('rows', 'memberOptions'));
    }

    public function store(Request $request, FirebaseNotificationService $firebase): RedirectResponse
    {
        $payload = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:1000'],
            'target_scope' => ['required', Rule::in(['all', 'single', 'selected'])],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:members,id'],
            'severity' => ['required', Rule::in([
                Announcement::SEVERITY_NORMAL,
                Announcement::SEVERITY_MODERATE,
                Announcement::SEVERITY_STRICT,
                Announcement::SEVERITY_FINAL,
            ])],
        ]);

        $targetScope = (string) $payload['target_scope'];
        $memberIds = collect($payload['member_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($targetScope !== 'all' && $memberIds->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['member_ids' => 'Select at least one member for this target.']);
        }

        if ($targetScope === 'single' && $memberIds->count() !== 1) {
            return back()
                ->withInput()
                ->withErrors(['member_ids' => 'Select exactly one member for single-member notification.']);
        }

        $targetType = match ($targetScope) {
            'single' => 'SINGLE_MEMBER',
            'selected' => 'SELECTED_MEMBERS',
            default => 'ALL_MEMBERS',
        };

        $announcement = Announcement::query()->create([
            'title' => $payload['title'],
            'message' => $payload['message'],
            'target_type' => $targetType,
            'severity' => $payload['severity'],
            'target_member_ids' => $targetScope === 'all' ? null : $memberIds->all(),
            'sent_by_user_id' => Auth::id(),
            'sent_at' => now(),
        ]);

        $tokensQuery = DB::table('member_device_tokens')
            ->whereNotNull('device_token');

        if ($targetScope !== 'all') {
            $tokensQuery->whereIn('member_id', $memberIds->all());
        }

        $tokens = $tokensQuery
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
                        'severity' => (string) $announcement->severity,
                        'target_type' => (string) $announcement->target_type,
                        'sound_profile' => $this->soundProfile((string) $announcement->severity),
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
            ->with('success', "Notification sent. Success: {$success}, Failed: {$failed}");
    }

    private function soundProfile(string $severity): string
    {
        return match ($severity) {
            Announcement::SEVERITY_STRICT => 'long',
            Announcement::SEVERITY_FINAL => 'long_urgent',
            Announcement::SEVERITY_MODERATE => 'medium',
            default => 'normal',
        };
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
