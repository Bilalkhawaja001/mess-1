<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Menu;
use App\Models\MemberProfileChangeRequest;
use App\Services\Payments\PaymentReconciliationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MemberApiController extends Controller
{
    public function __construct()
    {
        ini_set('serialize_precision', '-1');
    }
    public function login(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'member_id' => ['nullable', 'string', 'max:100'],
            'login' => ['nullable', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:100'],
            'identifier' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'max:100'],
            'mobile' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $login = trim((string) (
            $payload['login']
            ?? $payload['member_id']
            ?? $payload['username']
            ?? $payload['identifier']
            ?? $payload['email']
            ?? $payload['mobile']
            ?? $payload['phone']
            ?? ''
        ));

        if ($login === '') {
            return response()->json([
                'success' => false,
                'message' => 'Member ID, phone, email, or username is required',
            ], 422);
        }

        $row = DB::table('users')
            ->join('members', 'members.id', '=', 'users.member_id')
            ->leftJoin('messes', 'messes.id', '=', 'members.mess_id')
            ->where(function ($q) use ($login) {
                $q->where('users.username', $login)
                    ->orWhere('users.email', $login)
                    ->orWhere('members.member_code', $login)
                    ->orWhere('members.mobile_number', $login);
            })
            ->select([
                'users.id as user_id',
                'users.email',
                'users.password',
                  'users.must_change_password',
                  'users.password_changed_at',
                'users.is_active as user_is_active',
                'users.member_id as user_member_id',
                'members.id as member_id',
                'members.member_code',
                'members.name',
                'members.mobile_number',
                'members.mess_id',
                'members.department_name',
                'members.is_active as member_is_active',
                'messes.name as mess_name',
            ])
            ->first();

        if (! $row || ! Hash::check((string) $payload['password'], (string) $row->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (! (bool) $row->user_is_active || ! (bool) $row->member_is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account inactive',
            ], 403);
        }

        $token = Str::random(80);

        DB::table('users')
            ->where('id', $row->user_id)
            ->update([
                'remember_token' => hash('sha256', $token),
                'last_login_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'token' => $token,
            'token_type' => 'Bearer',
            'member' => $this->memberPayload($row),
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $row = $this->memberFromToken($request);

        if (! $row) {
            return $this->unauthenticated();
        }

        return response()->json([
            'success' => true,
            'member' => $this->memberPayload($row),
        ]);
    }


    public function storeProfileChangeRequest(Request $request): JsonResponse
    {
        $row = $this->memberFromToken($request);

        if (! $row) {
            return $this->unauthenticated();
        }

        $payload = $request->validate([
            'field_name' => ['required', 'in:email,mobile'],
            'new_value' => ['required', 'string', 'max:255'],
        ]);

        $fieldName = (string) $payload['field_name'];
        $newValue = trim((string) $payload['new_value']);

        if ($fieldName === MemberProfileChangeRequest::FIELD_EMAIL && ! filter_var($newValue, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid email address.',
            ], 422);
        }

        if ($fieldName === MemberProfileChangeRequest::FIELD_MOBILE) {
            $normalizedMobile = preg_replace('/[\s\-\(\)]/', '', $newValue);

            if (! preg_match('/^\+?[0-9]{10,15}$/', $normalizedMobile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter a valid mobile number.',
                ], 422);
            }

            $newValue = $normalizedMobile;
        }

        $oldValue = $fieldName === MemberProfileChangeRequest::FIELD_EMAIL
            ? ($row->email ?? null)
            : ($row->mobile_number ?? null);

        if ((string) $oldValue === $newValue) {
            return response()->json([
                'success' => false,
                'message' => 'New value is same as current value.',
            ], 422);
        }

        $existingPending = MemberProfileChangeRequest::query()
            ->where('member_id', $row->member_id)
            ->where('field_name', $fieldName)
            ->where('status', MemberProfileChangeRequest::STATUS_PENDING)
            ->latest('id')
            ->first();

        if ($existingPending) {
            return response()->json([
                'success' => false,
                'message' => 'A pending change request already exists for this field.',
                'request' => [
                    'id' => $existingPending->id,
                    'field_name' => $existingPending->field_name,
                    'old_value' => $existingPending->old_value,
                    'new_value' => $existingPending->new_value,
                    'status' => $existingPending->status,
                    'created_at' => optional($existingPending->created_at)->format('Y-m-d H:i:s'),
                ],
            ], 409);
        }

        $changeRequest = MemberProfileChangeRequest::query()->create([
            'member_id' => $row->member_id,
            'requested_by_user_id' => $row->user_id,
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'status' => MemberProfileChangeRequest::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile change request submitted for admin approval.',
            'request' => [
                'id' => $changeRequest->id,
                'field_name' => $changeRequest->field_name,
                'old_value' => $changeRequest->old_value,
                'new_value' => $changeRequest->new_value,
                'status' => $changeRequest->status,
                'created_at' => optional($changeRequest->created_at)->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $row = $this->memberFromToken($request);

        if (! $row) {
            return $this->unauthenticated();
        }

        $payload = $request->validate([
            'current_password' => ['nullable', 'string', 'max:255'],
            'new_password' => ['required', 'string', 'min:6', 'max:255', 'confirmed'],
        ]);

        $user = DB::table('users')->where('id', $row->user_id)->first();

        if (! $user) {
            return $this->unauthenticated();
        }

        $mustChange = (bool) ($user->must_change_password ?? false);
        $currentPassword = (string) ($payload['current_password'] ?? '');

        if (! $mustChange && ! Hash::check($currentPassword, (string) $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        if (Hash::check((string) $payload['new_password'], (string) $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'New password must be different from current password.',
            ], 422);
        }

        DB::table('users')
            ->where('id', $row->user_id)
            ->update([
                'password' => Hash::make((string) $payload['new_password']),
                'must_change_password' => 0,
                'password_changed_at' => now(),
                'remember_token' => null,
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully. Please login again.',
            'must_change_password' => false,
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $row = $this->memberFromToken($request);

        if (! $row) {
            return $this->unauthenticated();
        }

        $currentBill = (float) DB::table('billings')
            ->where('member_id', $row->member_id)
            ->orderByDesc('month_cycle')
            ->orderByDesc('id')
            ->value('net_payable');

        $openingBalance = (float) DB::table('member_ledgers')
            ->where('member_id', $row->member_id)
            ->selectRaw('COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) as balance')
            ->value('balance');

        $lastPayment = (float) DB::table('payments')
            ->where('member_id', $row->member_id)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->value('amount');

        $complaintsOpen = DB::table('complaints')
            ->where('member_id', $row->member_id)
            ->whereNotIn('status', ['closed', 'resolved', 'completed'])
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'member' => [
                    'id' => (int) $row->member_id,
                    'name' => (string) $row->name,
                    'member_id' => (string) $row->member_code,
                ],
                'current_bill' => $this->apiMoney($currentBill),
                'opening_balance' => $this->apiMoney($openingBalance),
                'complaints_open' => (int) $complaintsOpen,
                'notifications_unread' => $this->unreadAnnouncementCount((int) $row->member_id),
                'last_payment' => $this->apiMoney($lastPayment),
            ],
        ]);
    }



    public function notifications(Request $request): JsonResponse
    {
        $row = $this->memberFromToken($request);

        if (! $row) {
            return $this->unauthenticated();
        }

        $items = $this->announcementBaseQuery((int) $row->member_id)
            ->leftJoin('member_announcement_reads as reads', function ($join) use ($row) {
                $join->on('reads.announcement_id', '=', 'announcements.id')
                    ->where('reads.member_id', '=', (int) $row->member_id);
            })
            ->orderByDesc('announcements.sent_at')
            ->orderByDesc('announcements.id')
            ->limit(100)
            ->select([
                'announcements.id',
                'announcements.title',
                'announcements.message',
                'announcements.severity',
                'announcements.target_type',
                'announcements.sent_at',
                'announcements.created_at',
                'reads.id as read_id',
            ])
            ->get();

        return response()->json([
            'success' => true,
            'notifications' => $items->map(fn ($item) => [
                'id' => (int) $item->id,
                'title' => (string) $item->title,
                'body' => (string) $item->message,
                'message' => (string) $item->message,
                'date' => $item->sent_at ? \Illuminate\Support\Carbon::parse($item->sent_at)->format('d-M-Y H:i') : '',
                'created_at' => $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('Y-m-d H:i:s') : '',
                'severity' => (string) ($item->severity ?? 'normal'),
                'status' => (string) ($item->severity ?? 'normal'),
                'target_type' => (string) $item->target_type,
                'unread' => $item->read_id === null,
            ])->values(),
            'unread_count' => $this->unreadAnnouncementCount((int) $row->member_id),
        ]);
    }

    public function markNotificationsRead(Request $request): JsonResponse
    {
        $row = $this->memberFromToken($request);

        if (! $row) {
            return $this->unauthenticated();
        }

        $ids = $this->announcementBaseQuery((int) $row->member_id)
            ->pluck('announcements.id')
            ->map(fn ($id) => (int) $id)
            ->values();

        foreach ($ids as $id) {
            DB::table('member_announcement_reads')->updateOrInsert(
                [
                    'announcement_id' => $id,
                    'member_id' => (int) $row->member_id,
                ],
                [
                    'read_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        return response()->json([
            'success' => true,
            'read_count' => $ids->count(),
            'unread_count' => 0,
        ]);
    }

    public function currentBill(Request $request): JsonResponse
    {
        $row = $this->memberFromToken($request);

        if (! $row) {
            return $this->unauthenticated();
        }

        $bill = DB::table('billings')
            ->where('member_id', $row->member_id)
            ->orderByDesc('month_cycle')
            ->orderByDesc('id')
            ->first();

        $previous = DB::table('billings')
            ->where('member_id', $row->member_id)
            ->orderByDesc('month_cycle')
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        return response()->json([
            'success' => true,
            'total' => $bill ? $this->apiMoney($bill->net_payable) : 0,
            'total_payable' => $bill ? $this->apiMoney($bill->net_payable) : 0,
            'due_date' => $bill?->due_date ? \Illuminate\Support\Carbon::parse($bill->due_date)->format('d-M-Y') : '',
            'month' => $bill->month_cycle ?? '',
            'summary' => $bill ? [
                [
                    'label' => 'Net payable',
                    'amount' => $this->apiMoney($bill->net_payable),
                    'positive' => ((float) $bill->net_payable) <= 0,
                ],
            ] : [],
            'previous_months' => $previous->map(fn ($b) => [
                'month' => $b->month_cycle ?? '',
                'amount' => $this->apiMoney($b->net_payable),
                'paid' => ((float) $b->net_payable) <= 0,
            ])->values(),
        ]);
    }

    public function statement(Request $request): JsonResponse
    {
        $row = $this->memberFromToken($request);

        if (! $row) {
            return $this->unauthenticated();
        }

        $ledgerRows = DB::table('member_ledgers')
            ->where('member_id', $row->member_id)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $first = $ledgerRows->first();
        $last = $ledgerRows->last();

        $opening = $first
            ? ((float) $first->balance_after - (float) $first->debit + (float) $first->credit)
            : 0;

        $closing = $last ? (float) $last->balance_after : 0;

        return response()->json([
            'success' => true,
            'opening' => $this->apiMoney($opening),
            'opening_balance' => $this->apiMoney($opening),
            'closing' => $this->apiMoney($closing),
            'closing_balance' => $this->apiMoney($closing),
            'ledger' => $ledgerRows->map(function ($r) {
                $isCredit = ((float) $r->credit) > 0;
                $amount = $isCredit ? (float) $r->credit : (float) $r->debit;

                $parts = [];
                if (! empty($r->ref_type)) {
                    $parts[] = strtoupper((string) $r->ref_type);
                }
                if (! empty($r->ref_id)) {
                    $parts[] = '#' . $r->ref_id;
                }
                if (! empty($r->reason_code)) {
                    $parts[] = str_replace('_', ' ', (string) $r->reason_code);
                }

                return [
                    'date' => (string) $r->entry_date,
                    'title' => $parts !== [] ? implode(' - ', $parts) : ($isCredit ? 'Payment received' : 'Bill / charge'),
                    'description' => $parts !== [] ? implode(' - ', $parts) : ($isCredit ? 'Payment received' : 'Bill / charge'),
                    'amount' => $this->apiMoney($amount),
                    'credit' => $isCredit,
                    'running_balance' => $this->apiMoney($r->balance_after),
                ];
            })->values(),
        ]);
    }

    public function payments(Request $request): JsonResponse
    {
        $row = $this->memberFromToken($request);

        if (! $row) {
            return $this->unauthenticated();
        }

        $payments = DB::table('payments')
            ->where('member_id', $row->member_id)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'payments' => $payments->map(fn ($p) => [
                'date' => (string) $p->payment_date,
                'paid_at' => (string) $p->payment_date,
                'method' => (string) $p->method,
                'amount' => $this->apiMoney($p->amount),
                'receipt' => $p->reference_no ?? '',
                'receipt_no' => $p->reference_no ?? '',
                'status' => (string) $p->status,
            ])->values(),
        ]);
    }

    public function uploadPayment(Request $request, PaymentReconciliationService $reconciliationService): JsonResponse
    {
        $row = $this->memberFromToken($request);

        if (! $row) {
            return $this->unauthenticated();
        }

        $payload = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'payment_method' => ['nullable', 'string', 'max:80'],
            'screenshot' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $bill = DB::table('billings')
            ->where('member_id', $row->member_id)
            ->orderByDesc('month_cycle')
            ->orderByDesc('id')
            ->first();

        if (! $bill) {
            return response()->json([
                'success' => false,
                'message' => 'No bill found for this member',
            ], 422);
        }

        $methodCode = strtoupper(trim((string) ($payload['payment_method'] ?? 'MANUAL_BANK_TRANSFER')));
        $methodCode = str_replace([' ', '-'], '_', $methodCode);

        $method = DB::table('payment_methods')
            ->where('is_active', true)
            ->where(function ($q) use ($methodCode) {
                $q->where('code', $methodCode)
                    ->orWhere('name', $methodCode);
            })
            ->first();

        if (! $method) {
            $method = DB::table('payment_methods')
                ->where('code', 'MANUAL_BANK_TRANSFER')
                ->where('is_active', true)
                ->first();
        }

        if (! $method) {
            $method = DB::table('payment_methods')
                ->where('is_manual', true)
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
        }

        if (! $method) {
            return response()->json([
                'success' => false,
                'message' => 'No active manual payment method configured',
            ], 422);
        }

        $uploadedFile = $request->file('screenshot');
        $path = $uploadedFile->store('member-payment-screenshots/'.now()->format('Y/m'), 'local');
        $screenshotHash = hash_file('sha256', $uploadedFile->getRealPath());

        try {
            $payment = DB::transaction(function () use ($row, $bill, $method, $payload, $path, $screenshotHash, $reconciliationService) {
                $payment = Payment::query()->create([
                    'member_id' => $row->member_id,
                    'bill_id' => $bill->id,
                    'month_cycle' => (string) $bill->month_cycle,
                    'duplicate_guard_version' => Payment::DUPLICATE_GUARD_VERSION,
                    'payment_method_id' => $method->id,
                    'payment_ref' => 'APP-UPLOAD-'.now()->format('YmdHis').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
                    'payment_date' => now()->toDateString(),
                    'amount' => (float) $payload['amount'],
                    'currency' => 'PKR',
                    'method' => $method->code,
                    'reference_no' => $payload['reference_no'] ?? null,
                    'notes' => 'Member app payment screenshot uploaded. File: '.$path,
                    'status' => Payment::STATUS_RECONCILIATION_PENDING,
                    'posted_by_user_id' => null,
                ]);

                $reconciliation = $reconciliationService->createPending($payment);

                $meta = $reconciliation->meta ?? [];
                $meta['source'] = 'android_member_app';
                $meta['upload_type'] = 'payment_screenshot';
                $meta['screenshot_disk'] = 'local';
                $meta['screenshot_path'] = $path;
                $meta['screenshot_sha256'] = $screenshotHash;
                $meta['original_filename'] = request()->file('screenshot')?->getClientOriginalName();
                $meta['reference_no'] = $payload['reference_no'] ?? null;

                $reconciliation->meta = $meta;
                $reconciliation->notes = 'Member uploaded payment screenshot for admin review.';
                $reconciliation->save();

                return $payment->fresh();
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Payment upload failed',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment uploaded and sent for admin review',
            'payment' => [
                'id' => (int) $payment->id,
                'date' => (string) $payment->payment_date,
                'paid_at' => (string) $payment->payment_date,
                'method' => (string) $payment->method,
                'amount' => $this->apiMoney($payment->amount),
                'receipt' => $payment->reference_no ?? '',
                'receipt_no' => $payment->reference_no ?? '',
                'status' => (string) $payment->status,
            ],
        ], 201);
    }



    public function todayMenu(Request $request): JsonResponse
    {
        $row = $this->memberFromToken($request);

        if (! $row) {
            return $this->unauthenticated();
        }

        $buckets = [
            'BREAKFAST' => 'Breakfast',
            'LUNCH' => 'Lunch',
            'DINNER' => 'Dinner',
            'TEA_OTHER' => 'Tea / Other',
        ];

        $menus = Menu::query()
            ->where('status', Menu::STATUS_APPROVED)
            ->whereDate('menu_date', now()->toDateString())
            ->where('mess_id', $row->mess_id)
            ->orderByRaw("FIELD(meal_type, 'BREAKFAST', 'LUNCH', 'DINNER', 'TEA', 'OTHER')")
            ->orderBy('id')
            ->get();

        $meals = [];

        foreach ($menus as $menu) {
            $bucket = in_array($menu->meal_type, ['TEA', 'OTHER'], true) ? 'TEA_OTHER' : (string) $menu->meal_type;

            if (! array_key_exists($bucket, $buckets)) {
                continue;
            }

            $lines = preg_split('/\r\n|\r|\n/', trim((string) $menu->items_text));
            $items = collect($lines)
                ->map(fn ($line) => trim((string) $line))
                ->filter()
                ->values()
                ->all();

            if (trim((string) $menu->title) !== '') {
                array_unshift($items, trim((string) $menu->title));
            }

            $meals[] = [
                'name' => $buckets[$bucket],
                'meal' => $buckets[$bucket],
                'meal_type' => $bucket,
                'items' => $items,
                'date' => optional($menu->menu_date)->format('Y-m-d') ?? now()->toDateString(),
            ];
        }

        return response()->json([
            'success' => true,
            'date' => now()->toDateString(),
            'meals' => $meals,
        ]);
    }
    public function complaints(Request $request): JsonResponse
    {
        $row = $this->memberFromToken($request);

        if (! $row) {
            return $this->unauthenticated();
        }

        $complaints = DB::table('complaints')
            ->where('member_id', $row->member_id)
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'complaints' => $complaints->map(fn ($c) => [
                'no' => (string) $c->complaint_no,
                'id' => (string) $c->id,
                'title' => (string) $c->subject,
                'subject' => (string) $c->subject,
                'date' => (string) $c->created_at,
                'created_at' => (string) $c->created_at,
                'description' => (string) ($c->message ?? $c->description ?? ''),
                'status' => (string) $c->status,
            ])->values(),
        ]);
    }

    public function createComplaint(Request $request): JsonResponse
    {
        $row = $this->memberFromToken($request);

        if (! $row) {
            return $this->unauthenticated();
        }

        $payload = $request->validate([
            'category' => ['nullable', 'string', 'max:120'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        $complaintNo = 'CMP-' . now()->format('Ymd') . '-' . str_pad((string) (DB::table('complaints')->count() + 1), 5, '0', STR_PAD_LEFT);

        $id = DB::table('complaints')->insertGetId([
            'complaint_no' => $complaintNo,
            'user_id' => $row->user_id,
            'member_id' => $row->member_id,
            'submitted_by_name' => $row->name,
            'submitted_by_contact' => $row->mobile_number ?? null,
            'type' => 'COMPLAINT',
            'category' => $payload['category'] ?? 'OTHER',
            'subject' => $payload['subject'],
            'description' => $payload['description'],
            'message' => $payload['description'],
            'priority' => 'NORMAL',
            'status' => 'PENDING',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'complaint' => [
                'id' => $id,
                'no' => $complaintNo,
                'status' => 'PENDING',
            ],
        ], 201);
    }

    private function apiMoney(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function memberFromToken(Request $request): ?object
    {
        $token = $this->extractToken($request);

        if ($token === '') {
            return null;
        }

        return DB::table('users')
            ->join('members', 'members.id', '=', 'users.member_id')
            ->leftJoin('messes', 'messes.id', '=', 'members.mess_id')
            ->where('users.remember_token', hash('sha256', $token))
            ->where('users.is_active', true)
            ->where('members.is_active', true)
            ->select([
                'users.id as user_id',
                'users.email',
                'users.member_id as user_member_id',
                  'users.must_change_password',
                  'users.password_changed_at',
                'members.id as member_id',
                'members.member_code',
                'members.name',
                'members.mobile_number',
                'members.mess_id',
                'members.department_name',
                'members.is_active as member_is_active',
                'messes.name as mess_name',
            ])
            ->first();
    }

    private function extractToken(Request $request): string
    {
        $token = (string) $request->bearerToken();

        if ($token !== '') {
            return $token;
        }

        $headers = [
            (string) $request->header('Authorization'),
            (string) $request->server('HTTP_AUTHORIZATION'),
            (string) $request->server('REDIRECT_HTTP_AUTHORIZATION'),
            (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''),
            (string) ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''),
        ];

        foreach ($headers as $header) {
            $header = trim($header);
            if (stripos($header, 'Bearer ') === 0) {
                return trim(substr($header, 7));
            }
        }

        $xToken = trim((string) $request->header('X-Member-Token'));
        if ($xToken !== '') {
            return $xToken;
        }

        return trim((string) ($request->input('token') ?? $request->query('token') ?? ''));
    }

    private function unauthenticated(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated',
        ], 401);
    }

    private function memberPayload(object $row): array
    {
        return [
            'id' => (int) $row->member_id,
            'name' => (string) $row->name,
            'member_id' => (string) $row->member_code,
            'mobile' => $row->mobile_number ?? null,
            'email' => $row->email ?? null,
            'department' => $row->department_name ?? null,
            'room_no' => null,
            'mess_name' => $row->mess_name ?? null,
              'must_change_password' => (bool) ($row->must_change_password ?? false),
        ];
    }
    private function unreadAnnouncementCount(int $memberId): int
    {
        return (int) $this->announcementBaseQuery($memberId)
            ->leftJoin('member_announcement_reads as reads', function ($join) use ($memberId) {
                $join->on('reads.announcement_id', '=', 'announcements.id')
                    ->where('reads.member_id', '=', $memberId);
            })
            ->whereNull('reads.id')
            ->count('announcements.id');
    }

    private function announcementBaseQuery(int $memberId)
    {
        return DB::table('announcements')
            ->whereNotNull('announcements.sent_at')
            ->where(function ($query) use ($memberId) {
                $query->where('announcements.target_type', 'ALL_MEMBERS')
                    ->orWhereJsonContains('announcements.target_member_ids', $memberId);
            });
    }

}
