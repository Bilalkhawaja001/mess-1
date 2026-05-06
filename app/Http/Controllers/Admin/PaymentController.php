<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\StorePaymentRequest;
use App\Models\Member;
use App\Models\MemberLedger;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentReconciliation;
use App\Models\PaymentTransaction;
use App\Services\PaymentEditService;
use App\Services\Payments\PaymentAttemptService;
use App\Services\Payments\PaymentReconciliationService;
use App\Services\Payments\PaymentTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $members = Member::query()->where('is_active', true)->orderBy('member_code')->get();
        $methods = PaymentMethod::query()->where('is_active', true)->orderBy('name')->get();

        $rows = Payment::query()->with(['member', 'bill', 'methodRecord'])->orderByDesc('created_at');

        if ($request->filled('member_id')) {
            $rows->where('member_id', (int) $request->input('member_id'));
        }
        if ($request->filled('bill_id')) {
            $rows->where('bill_id', (int) $request->input('bill_id'));
        }
        if ($request->filled('status')) {
            $rows->where('status', (string) $request->input('status'));
        }
        if ($request->filled('method')) {
            $rows->where('method', (string) $request->input('method'));
        }
        if ($request->filled('ref')) {
            $ref = (string) $request->input('ref');
            $rows->where(function ($q) use ($ref) {
                $q->where('payment_ref', 'like', "%{$ref}%")
                    ->orWhere('reference_no', 'like', "%{$ref}%");
            });
        }

        return view('admin.payments.index', [
            'members' => $members,
            'methods' => $methods,
            'rows' => $rows->limit(300)->get(),
            'txns' => PaymentTransaction::query()->latest('id')->limit(200)->get(),
            'reconciliations' => PaymentReconciliation::query()->latest('id')->limit(200)->get(),
        ]);
    }

    public function store(StorePaymentRequest $request, PaymentAttemptService $attemptService, PaymentTransactionService $transactionService): RedirectResponse
    {
        [$payment, $attempt] = $attemptService->createAttempt(
            (int) $request->input('member_id'),
            (int) $request->input('bill_id'),
            (int) $request->input('payment_method_id'),
            (float) $request->input('amount'),
            (int) Auth::id(),
        );

        $transactionService->recordFromAttempt($payment, $attempt, [
            'status' => Payment::STATUS_INITIATED,
            'merchant_ref' => $request->input('reference_no') ?: null,
            'raw_request_summary' => ['source' => 'admin.manual_record'],
            'raw_response_summary' => ['note' => 'No live charging. Pending verify.'],
            'idempotency_key' => $request->input('idempotency_key') ?: null,
        ], (int) Auth::id());

        return redirect()->route('admin.payments.index')->with('success', 'Payment attempt + transaction created (pending verification).');
    }

    public function memberBillLookup(Request $request): JsonResponse
    {
        $rawMember = trim((string) $request->query('member_id', ''));
        if ($rawMember === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Member ID is required',
            ], 422);
        }

        $members = Member::query()
            ->where(function ($query) use ($rawMember) {
                $query->where('member_code', $rawMember)
                    ->orWhere('name', 'like', '%' . $rawMember . '%');

                if (ctype_digit($rawMember)) {
                    $query->orWhere('id', (int) $rawMember);
                }
            })
            ->orderByRaw('CASE WHEN member_code = ? THEN 0 ELSE 1 END', [$rawMember])
            ->orderBy('member_code')
            ->limit(10)
            ->get();

        if ($members->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'No member found',
            ]);
        }

        $matches = $members->map(function (Member $member) {
            $bill = Billing::query()
                ->where('member_id', $member->id)
                ->whereDoesntHave('payments', function ($query) {
                    $query->whereIn('status', [Payment::STATUS_SUCCESS, Payment::STATUS_RECONCILED]);
                })
                ->orderByDesc('month_cycle')
                ->orderByDesc('id')
                ->first();

            if (! $bill) {
                $bill = Billing::query()
                    ->where('member_id', $member->id)
                    ->orderByDesc('month_cycle')
                    ->orderByDesc('id')
                    ->first();
            }

            return [
                'member_id' => $member->id,
                'member_code' => $member->member_code,
                'member_name' => $member->name,
                'bill_id' => $bill?->id,
                'message' => $bill ? 'Bill found' : 'No bill found for this member',
            ];
        })->filter(fn (array $row) => ! empty($row['bill_id']))->values();

        if ($matches->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'No member found',
            ]);
        }

        return response()->json([
            'ok' => true,
            'matches' => $matches,
        ]);
    }

    public function edit(Payment $payment, Request $request, PaymentEditService $service): RedirectResponse
    {
        $payload = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', 'max:50'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $ok = $service->edit($payment, [
            'payment_date' => (string) $payload['payment_date'],
            'amount' => (float) $payload['amount'],
            'method' => (string) $payload['method'],
            'reference_no' => $payload['reference_no'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ], (int) Auth::id(), (string) $payload['reason']);

        if (! $ok) {
            return back()->with('error', 'Approved/success payments cannot be edited.');
        }

        return redirect()->route('admin.payments.index')->with('success', 'Payment edited.');
    }

    public function approve(Payment $payment, PaymentTransactionService $transactionService): RedirectResponse
    {
        if (in_array($payment->status, [Payment::STATUS_SUCCESS, Payment::STATUS_RECONCILIATION_PENDING, Payment::STATUS_RECONCILED], true)) {
            return back()->with('info', 'Payment already verified.');
        }

        $txn = $payment->transactions()->latest('id')->first();
        if (! $txn) {
            return back()->with('error', 'No transaction found for this payment.');
        }

        DB::transaction(function () use ($payment, $txn, $transactionService) {
            $transactionService->manualVerify($txn, (int) Auth::id(), true);

            $existingLedger = MemberLedger::query()
                ->where('member_id', $payment->member_id)
                ->where('ref_type', 'PAYMENT')
                ->where('ref_id', $payment->id)
                ->first();

            if (! $existingLedger) {
                $lastBal = (float) (MemberLedger::query()
                    ->where('member_id', $payment->member_id)
                    ->orderByDesc('entry_date')
                    ->orderByDesc('id')
                    ->value('balance_after') ?? 0);

                $newBal = round($lastBal - (float) $payment->amount, 2);

                MemberLedger::query()->create([
                    'member_id' => $payment->member_id,
                    'entry_date' => $payment->payment_date,
                    'debit' => 0,
                    'credit' => $payment->amount,
                    'ref_type' => 'PAYMENT',
                    'ref_id' => $payment->id,
                    'balance_after' => $newBal,
                    'reason_code' => 'PAYMENT_MANUAL_VERIFIED',
                    'posted_by_user_id' => Auth::id(),
                ]);
            }
        });

        return redirect()->route('admin.payments.index')->with('success', 'Payment manually verified and posted to ledger.');
    }

    public function verifyTransaction(PaymentTransaction $transaction, Request $request, PaymentTransactionService $service): RedirectResponse
    {
        $request->validate(['mark_success' => ['nullable', 'boolean']]);
        $service->manualVerify($transaction, (int) Auth::id(), (bool) $request->boolean('mark_success', true));

        return back()->with('success', 'Transaction manual verification applied.');
    }

    public function reconcile(PaymentReconciliation $reconciliation, Request $request, PaymentReconciliationService $service): RedirectResponse
    {
        $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);
        $service->reconcile($reconciliation, (int) Auth::id(), (string) $request->input('notes', ''));

        return back()->with('success', 'Reconciliation updated.');
    }
}
