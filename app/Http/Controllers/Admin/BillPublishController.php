<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\BillPublishRun;
use App\Models\MemberDeviceToken;
use App\Services\Firebase\FirebaseNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BillPublishController extends Controller
{
    public function index(Request $request): View
    {
        $monthCycle = trim((string) $request->query('month_cycle', ''));

        if ($monthCycle === '') {
            $monthCycle = (string) (Billing::query()
                ->select('month_cycle')
                ->distinct()
                ->orderByDesc('month_cycle')
                ->value('month_cycle') ?? '');
        }

        $months = Billing::query()
            ->select('month_cycle')
            ->distinct()
            ->orderByDesc('month_cycle')
            ->pluck('month_cycle');

        $bills = collect();

        if ($monthCycle !== '') {
            $bills = Billing::query()
                ->with('member')
                ->where('month_cycle', $monthCycle)
                ->orderBy('member_id')
                ->get();
        }

        $runs = BillPublishRun::query()
            ->with('publisher')
            ->latest('id')
            ->limit(20)
            ->get();

        $summary = [
            'bill_count' => $bills->count(),
            'total_bill_amount' => round((float) $bills->sum('net_payable'), 2),
            'members_with_tokens' => $monthCycle !== ''
                ? MemberDeviceToken::query()
                    ->whereIn('member_id', $bills->pluck('member_id')->filter()->unique())
                    ->distinct('member_id')
                    ->count('member_id')
                : 0,
        ];

        return view('admin.bill_publish.index', compact('monthCycle', 'months', 'bills', 'runs', 'summary'));
    }

    public function store(Request $request, FirebaseNotificationService $firebase): RedirectResponse
    {
        $payload = $request->validate([
            'month_cycle' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $monthCycle = (string) $payload['month_cycle'];

        $bills = Billing::query()
            ->with('member')
            ->where('month_cycle', $monthCycle)
            ->orderBy('member_id')
            ->get();

        if ($bills->isEmpty()) {
            return redirect()
                ->route('admin.bill-publish.index', ['month_cycle' => $monthCycle])
                ->with('warning', 'No bills found for selected month.');
        }

        $run = BillPublishRun::query()->create([
            'month_cycle' => $monthCycle,
            'published_by_user_id' => Auth::id(),
            'published_at' => now(),
            'bill_count' => $bills->count(),
            'total_bill_amount' => round((float) $bills->sum('net_payable'), 2),
        ]);

        $success = 0;
        $failed = 0;
        $totalTokens = 0;

        foreach ($bills as $bill) {
            $tokens = MemberDeviceToken::query()
                ->where('member_id', $bill->member_id)
                ->pluck('device_token')
                ->filter()
                ->unique()
                ->values();

            if ($tokens->isEmpty()) {
                continue;
            }

            $amount = number_format((float) $bill->net_payable, 2);
            $memberName = trim((string) ($bill->member?->name ?? 'Member'));

            foreach ($tokens as $token) {
                $totalTokens++;
                $token = (string) $token;

                try {
                    $result = $firebase->sendToToken(
                        $token,
                        'New Bill Published',
                        "Your mess bill for {$monthCycle} is PKR {$amount}.",
                        [
                            'type' => 'bill_published',
                            'month_cycle' => $monthCycle,
                            'bill_id' => (string) $bill->id,
                            'member_id' => (string) $bill->member_id,
                            'amount' => (string) $bill->net_payable,
                        ]
                    );

                    if (($result['ok'] ?? false) === true) {
                        $success++;
                    } else {
                        $failed++;
                        Log::warning('BILL_PUBLISH_FCM_FAILED', [
                            'run_id' => $run->id,
                            'bill_id' => $bill->id,
                            'member_id' => $bill->member_id,
                            'member_name' => $memberName,
                            'token_tail' => substr($token, -12),
                            'result' => $result,
                        ]);
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('BILL_PUBLISH_FCM_EXCEPTION', [
                        'run_id' => $run->id,
                        'bill_id' => $bill->id,
                        'member_id' => $bill->member_id,
                        'member_name' => $memberName,
                        'token_tail' => substr($token, -12),
                        'message' => $e->getMessage(),
                    ]);
                    report($e);
                }
            }
        }

        $run->update([
            'total_tokens' => $totalTokens,
            'success_count' => $success,
            'failed_count' => $failed,
        ]);

        return redirect()
            ->route('admin.bill-publish.index', ['month_cycle' => $monthCycle])
            ->with('success', "Bill published. Success: {$success}, Failed: {$failed}");
    }
}
