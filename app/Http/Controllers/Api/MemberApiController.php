<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
                'users.is_active as user_is_active',
                'users.member_id as user_member_id',
                'members.id as member_id',
                'members.member_code',
                'members.name',
                'members.mobile_number',
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
                'notifications_unread' => 0,
                'last_payment' => $this->apiMoney($lastPayment),
            ],
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
            'due_date' => '',
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
                'members.id as member_id',
                'members.member_code',
                'members.name',
                'members.mobile_number',
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
        ];
    }
}
