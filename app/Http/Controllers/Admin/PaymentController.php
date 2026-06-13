<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\StorePaymentRequest;
use App\Models\Billing;
use App\Models\Member;
use App\Models\MemberLedger;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentReconciliation;
use App\Models\PaymentTransaction;
use App\Services\PaymentEditService;
use App\Services\Payments\DuplicateActivePaymentException;
use App\Services\Payments\PaymentAttemptService;
use App\Services\Payments\PaymentDuplicateGuard;
use App\Services\Payments\PaymentReconciliationService;
use App\Services\Payments\PaymentTransactionService;
use Illuminate\Database\QueryException;
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

        $rows = Payment::query()->with(['member', 'bill', 'methodRecord', 'reconciliations'])->orderByDesc('created_at');

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

        $selectedMonthCycle = (string) $request->input('month_cycle', '');
        if ($selectedMonthCycle === '') {
            $selectedMonthCycle = (string) (Billing::query()->max('month_cycle') ?? '');
        }

        $billingSummaryQuery = Billing::query();
        if ($selectedMonthCycle !== '') {
            $billingSummaryQuery->where('month_cycle', $selectedMonthCycle);
        }

        $postedBillAmount = (float) $billingSummaryQuery->sum('net_payable');
        $postedBillCount = (clone $billingSummaryQuery)->count();

        $successfulPaymentStatuses = [
            Payment::STATUS_APPROVED,
            Payment::STATUS_SUCCESS,
            Payment::STATUS_RECONCILIATION_PENDING,
            Payment::STATUS_RECONCILED,
        ];

        $receivedPaymentsQuery = Payment::query()
            ->whereIn('status', $successfulPaymentStatuses)
            ->whereNotNull('bill_id');

        if ($selectedMonthCycle !== '') {
            $receivedPaymentsQuery->whereHas('bill', function ($query) use ($selectedMonthCycle) {
                $query->where('month_cycle', $selectedMonthCycle);
            });
        }

        $receivedPaymentAmount = (float) $receivedPaymentsQuery->sum('amount');
        $receivedPaymentCount = (clone $receivedPaymentsQuery)->count();

        $pendingTransactionsQuery = PaymentTransaction::query()
            ->whereNotIn('status', [Payment::STATUS_SUCCESS, Payment::STATUS_FAILED]);

        if ($selectedMonthCycle !== '') {
            $pendingTransactionsQuery->whereHas('payment.bill', function ($query) use ($selectedMonthCycle) {
                $query->where('month_cycle', $selectedMonthCycle);
            });
        }

        $pendingTransactionCount = (clone $pendingTransactionsQuery)->count();
        $pendingTransactionAmount = (float) $pendingTransactionsQuery->sum('amount');
        $pendingBalanceAmount = max(round($postedBillAmount - $receivedPaymentAmount, 2), 0);

        $paymentRows = $rows->limit(300)->get();

        $proofMap = [];
        foreach ($paymentRows as $paymentRow) {
            $proofRow = $paymentRow->reconciliations->sortByDesc('id')->first();
            $meta = $proofRow?->meta ?? [];
            if (! is_array($meta)) {
                $meta = json_decode((string) $meta, true) ?: [];
            }
            if (! empty($meta['screenshot_path'])) {
                $proofMap[$paymentRow->id] = [
                    'url' => route('admin.payments.proof', $paymentRow),
                    'source' => $meta['source'] ?? '',
                ];
            }
        }

        return view('admin.payments.index', [
            'members' => $members,
            'methods' => $methods,
            'rows' => $paymentRows,
            'proofMap' => $proofMap,
            'txns' => PaymentTransaction::query()->latest('id')->limit(200)->get(),
            'reconciliations' => PaymentReconciliation::query()->latest('id')->limit(200)->get(),
            'selectedMonthCycle' => $selectedMonthCycle,
            'billingMonths' => Billing::query()->select('month_cycle')->distinct()->orderByDesc('month_cycle')->pluck('month_cycle'),
            'postedBillAmount' => $postedBillAmount,
            'postedBillCount' => $postedBillCount,
            'receivedPaymentAmount' => $receivedPaymentAmount,
            'receivedPaymentCount' => $receivedPaymentCount,
            'pendingTransactionAmount' => $pendingTransactionAmount,
            'pendingTransactionCount' => $pendingTransactionCount,
            'pendingBalanceAmount' => $pendingBalanceAmount,
        ]);
    }

    public function store(StorePaymentRequest $request, PaymentAttemptService $attemptService, PaymentTransactionService $transactionService, PaymentDuplicateGuard $duplicateGuard): RedirectResponse
    {
        $memberId = (int) $request->input('member_id');
        $billId = (int) $request->input('bill_id');
        $methodId = (int) $request->input('payment_method_id');
        $amount = round((float) $request->input('amount'), 2);
        $userId = (int) Auth::id();

        try {
            $payment = DB::transaction(function () use ($request, $memberId, $billId, $methodId, $amount, $userId, $duplicateGuard) {
                $bill = $duplicateGuard->lockBill($billId, $memberId);
                $monthCycle = (string) $bill->month_cycle;

                $duplicateGuard->assertNoActiveDuplicate($memberId, $monthCycle);

                $method = PaymentMethod::query()
                    ->whereKey($methodId)
                    ->where('is_active', true)
                    ->firstOrFail();

                $payment = Payment::query()->create($duplicateGuard->withGuardAttributes([
                    'member_id' => $memberId,
                    'bill_id' => $bill->id,
                    'payment_method_id' => $method->id,
                    'payment_ref' => 'MANPAY-'.now()->format('YmdHis').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
                    'payment_date' => $request->input('payment_date') ?: now()->toDateString(),
                    'amount' => $amount,
                    'currency' => 'PKR',
                    'method' => $method->code,
                    'reference_no' => $request->input('reference_no') ?: null,
                    'notes' => $request->input('notes') ?: null,
                    'status' => Payment::STATUS_APPROVED,
                    'posted_by_user_id' => $userId,
                    'approved_by_user_id' => $userId,
                    'approved_at' => now(),
                ], $monthCycle));

            $exists = MemberLedger::query()
                ->where('member_id', $memberId)
                ->where('ref_type', 'PAYMENT')
                ->where('ref_id', $payment->id)
                ->exists();

            if (! $exists) {
                $lastBal = (float) (MemberLedger::query()
                    ->where('member_id', $memberId)
                    ->orderByDesc('entry_date')
                    ->orderByDesc('id')
                    ->value('balance_after') ?? 0);

                MemberLedger::query()->create([
                    'member_id' => $memberId,
                    'entry_date' => $payment->payment_date,
                    'debit' => 0,
                    'credit' => $amount,
                    'ref_type' => 'PAYMENT',
                    'ref_id' => $payment->id,
                    'balance_after' => round($lastBal - $amount, 2),
                    'reason_code' => 'PAYMENT_APPROVAL',
                    'posted_by_user_id' => $userId,
                ]);
            }

                return $payment;
            });
        } catch (DuplicateActivePaymentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            if (PaymentDuplicateGuard::isGuardUniqueIndexViolation($e)) {
                return back()->withInput()->with('error', 'Active payment already exists for this member/month.');
            }

            throw $e;
        }

        return redirect()->route('admin.payments.index')->with('success', 'Manual payment posted to ledger. Payment ID: '.$payment->id);
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
                'department' => $member->department_name,
                'bill_id' => $bill?->id,
                'message' => $bill ? 'Bill found' : 'No bill found for this member',
            ];
        })->values();

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

    public function approve(Payment $payment, PaymentTransactionService $transactionService, PaymentDuplicateGuard $duplicateGuard): RedirectResponse
    {
        if (in_array($payment->status, [Payment::STATUS_SUCCESS, Payment::STATUS_RECONCILIATION_PENDING, Payment::STATUS_RECONCILED], true)) {
            return back()->with('info', 'Payment already verified.');
        }

        $txn = $payment->transactions()->latest('id')->first();
        if (! $txn) {
            return back()->with('error', 'No transaction found for this payment.');
        }

        try {
            DB::transaction(function () use ($payment, $txn, $transactionService, $duplicateGuard) {
                $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
                $bill = $duplicateGuard->lockBill((int) $lockedPayment->bill_id, (int) $lockedPayment->member_id);
                $monthCycle = (string) $bill->month_cycle;

                $duplicateGuard->assertNoActiveDuplicate((int) $lockedPayment->member_id, $monthCycle, (int) $lockedPayment->id);
                $duplicateGuard->applyGuardAttributes($lockedPayment, Payment::STATUS_SUCCESS, $monthCycle)->save();

                $lockedTxn = PaymentTransaction::query()->whereKey($txn->id)->lockForUpdate()->firstOrFail();
                $transactionService->manualVerify($lockedTxn, (int) Auth::id(), true);

                $lockedPayment = $lockedPayment->fresh();

                $existingLedger = MemberLedger::query()
                    ->where('member_id', $lockedPayment->member_id)
                    ->where('ref_type', 'PAYMENT')
                    ->where('ref_id', $lockedPayment->id)
                    ->first();

            if (! $existingLedger) {
                $lastBal = (float) (MemberLedger::query()
                    ->where('member_id', $lockedPayment->member_id)
                    ->orderByDesc('entry_date')
                    ->orderByDesc('id')
                    ->value('balance_after') ?? 0);

                $newBal = round($lastBal - (float) $lockedPayment->amount, 2);

                MemberLedger::query()->create([
                    'member_id' => $lockedPayment->member_id,
                    'entry_date' => $lockedPayment->payment_date,
                    'debit' => 0,
                    'credit' => $lockedPayment->amount,
                    'ref_type' => 'PAYMENT',
                    'ref_id' => $lockedPayment->id,
                    'balance_after' => $newBal,
                    'reason_code' => 'PAYMENT_MANUAL_VERIFIED',
                    'posted_by_user_id' => Auth::id(),
                ]);
            }
            });
        } catch (DuplicateActivePaymentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            if (PaymentDuplicateGuard::isGuardUniqueIndexViolation($e)) {
                return back()->with('error', 'Active payment already exists for this member/month.');
            }

            throw $e;
        }

        return redirect()->route('admin.payments.index')->with('success', 'Payment manually verified and posted to ledger.');
    }

    public function approveUploadedProof(Payment $payment, PaymentDuplicateGuard $duplicateGuard): RedirectResponse
    {
        if ($payment->status !== Payment::STATUS_RECONCILIATION_PENDING) {
            return back()->with('error', 'Only pending review uploaded payments can be approved.');
        }

        $proofRow = $payment->reconciliations()->latest('id')->first();
        $meta = $proofRow?->meta ?? [];

        if (! is_array($meta)) {
            $meta = json_decode((string) $meta, true) ?: [];
        }

        $path = (string) ($meta['screenshot_path'] ?? '');
        $disk = (string) ($meta['screenshot_disk'] ?? 'local');
        $expectedHash = (string) ($meta['screenshot_sha256'] ?? '');

        if ($path === '' || ! in_array($disk, ['local', 'public'], true) || ! \Illuminate\Support\Facades\Storage::disk($disk)->exists($path)) {
            return back()->with('error', 'Payment proof file is missing. Cannot approve.');
        }

        if ($expectedHash !== '') {
            $actualHash = hash_file('sha256', \Illuminate\Support\Facades\Storage::disk($disk)->path($path));
            if (! hash_equals($expectedHash, $actualHash)) {
                return back()->with('error', 'Payment proof file integrity check failed. Cannot approve.');
            }
        }

        try {
            DB::transaction(function () use ($payment, $duplicateGuard) {
                $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
                $bill = $duplicateGuard->lockBill((int) $lockedPayment->bill_id, (int) $lockedPayment->member_id);
                $monthCycle = (string) $bill->month_cycle;

                $duplicateGuard->assertNoActiveDuplicate((int) $lockedPayment->member_id, $monthCycle, (int) $lockedPayment->id);
                $duplicateGuard->applyGuardAttributes($lockedPayment, Payment::STATUS_RECONCILED, $monthCycle);

                $existingLedger = MemberLedger::query()
                    ->where('member_id', $lockedPayment->member_id)
                    ->where('ref_type', 'PAYMENT')
                    ->where('ref_id', $lockedPayment->id)
                    ->first();

            if (! $existingLedger) {
                $lastBal = (float) (MemberLedger::query()
                    ->where('member_id', $lockedPayment->member_id)
                    ->orderByDesc('entry_date')
                    ->orderByDesc('id')
                    ->value('balance_after') ?? 0);

                $newBal = round($lastBal - (float) $lockedPayment->amount, 2);

                MemberLedger::query()->create([
                    'member_id' => $lockedPayment->member_id,
                    'entry_date' => $lockedPayment->payment_date,
                    'debit' => 0,
                    'credit' => $lockedPayment->amount,
                    'ref_type' => 'PAYMENT',
                    'ref_id' => $lockedPayment->id,
                    'balance_after' => $newBal,
                    'reason_code' => 'ANDROID_PAYMENT_PROOF_APPROVED',
                    'posted_by_user_id' => Auth::id(),
                ]);
            }

            $lockedPayment->status = Payment::STATUS_RECONCILED;
            $lockedPayment->approved_by_user_id = Auth::id();
            $lockedPayment->approved_at = now();
            $lockedPayment->save();

            PaymentReconciliation::query()
                ->where('payment_id', $payment->id)
                ->update([
                    'status' => Payment::STATUS_RECONCILED,
                    'ledger_sync_status' => 'SYNCED',
                    'accounting_sync_status' => 'SYNCED',
                    'reconciled_by_user_id' => Auth::id(),
                    'reconciled_at' => now(),
                    'notes' => 'Android payment proof approved from admin payments screen.',
                    'updated_at' => now(),
                ]);
            });
        } catch (DuplicateActivePaymentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            if (PaymentDuplicateGuard::isGuardUniqueIndexViolation($e)) {
                return back()->with('error', 'Active payment already exists for this member/month.');
            }

            throw $e;
        }

        return redirect()->route('admin.payments.index')->with('success', 'Payment proof approved and posted to ledger.');
    }

    public function uploadedProof(Payment $payment)
    {
        $proofRow = $payment->reconciliations()->latest('id')->first();
        $meta = $proofRow?->meta ?? [];

        if (! is_array($meta)) {
            $meta = json_decode((string) $meta, true) ?: [];
        }

        $path = (string) ($meta['screenshot_path'] ?? '');
        $disk = (string) ($meta['screenshot_disk'] ?? 'local');

        if ($path === '') {
            abort(404);
        }

        if (! in_array($disk, ['local', 'public'], true)) {
            abort(404);
        }

        if (! \Illuminate\Support\Facades\Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        $fullPath = \Illuminate\Support\Facades\Storage::disk($disk)->path($path);

        return response()->file($fullPath, [
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function rejectUploadedProof(Payment $payment, Request $request): RedirectResponse
    {
        if ($payment->status !== Payment::STATUS_RECONCILIATION_PENDING) {
            return back()->with('error', 'Only pending review uploaded payments can be rejected.');
        }

        $payload = $request->validate([
            'reject_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($payment, $payload) {
            $payment->status = Payment::STATUS_FAILED;
            $payment->notes = trim(($payment->notes ? $payment->notes.PHP_EOL : '').'Rejected: '.($payload['reject_reason'] ?? 'Payment proof rejected by admin.'));
            $payment->save();

            PaymentReconciliation::query()
                ->where('payment_id', $payment->id)
                ->update([
                    'status' => Payment::STATUS_FAILED,
                    'ledger_sync_status' => 'REJECTED',
                    'accounting_sync_status' => 'REJECTED',
                    'mismatch_reason' => $payload['reject_reason'] ?? 'Rejected by admin',
                    'notes' => 'Android payment proof rejected from admin payments screen.',
                    'updated_at' => now(),
                ]);
        });

        return redirect()->route('admin.payments.index')->with('success', 'Payment proof rejected.');
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
