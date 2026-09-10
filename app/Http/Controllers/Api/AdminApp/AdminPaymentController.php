<?php

namespace App\Http\Controllers\Api\AdminApp;

use App\Models\Member;
use App\Models\MemberLedger;
use App\Models\Billing;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\Payments\DuplicateActivePaymentException;
use App\Services\Payments\ManualPaymentService;
use App\Services\Payments\PaymentDuplicateGuard;
use App\Services\Payments\UploadedProofService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminPaymentController extends AdminAuthController
{
    public function pending(Request $request): JsonResponse
    {
        $user = $this->requirePermission($request, 'payments.view_admin');
        if (! $user) {
            return $this->user($request) ? $this->forbidden() : $this->unauthenticated();
        }

        $rows = Payment::query()
            ->with(['member:id,member_code,name'])
            ->where('status', Payment::STATUS_RECONCILIATION_PENDING)
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'payments' => $rows->map(fn ($p) => [
                'id' => (int) $p->id,
                'member_code' => $p->member->member_code ?? null,
                'member_name' => $p->member->name ?? null,
                'amount' => number_format((float) $p->amount, 2, '.', ''),
                'payment_date' => optional($p->payment_date)->format('Y-m-d'),
                'method' => $p->method,
                'reference_no' => $p->reference_no,
                'month_cycle' => $p->month_cycle,
                'notes' => $p->notes,
                'has_proof' => $p->reconciliations()->exists(),
            ]),
        ]);
    }

    public function proof(Request $request, int $id)
    {
        $user = $this->requirePermission($request, 'payments.view_admin');
        if (! $user) {
            return $this->user($request) ? $this->forbidden() : $this->unauthenticated();
        }

        $payment = Payment::find($id);
        if (! $payment) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $proofRow = $payment->reconciliations()->latest('id')->first();
        $meta = $proofRow?->meta ?? [];

        if (! is_array($meta)) {
            $meta = json_decode((string) $meta, true) ?: [];
        }

        $path = (string) ($meta['screenshot_path'] ?? '');
        $disk = (string) ($meta['screenshot_disk'] ?? 'local');

        if ($path === '' || ! in_array($disk, ['local', 'public'], true) || ! Storage::disk($disk)->exists($path)) {
            return response()->json(['success' => false, 'message' => 'Proof not available'], 404);
        }

        return response()->file(Storage::disk($disk)->path($path), [
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function approve(Request $request, int $id, UploadedProofService $service): JsonResponse
    {
        $user = $this->requirePermission($request, 'payment.approve');
        if (! $user) {
            return $this->user($request) ? $this->forbidden() : $this->unauthenticated();
        }

        $payment = Payment::find($id);
        if (! $payment) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        if ($payment->status !== Payment::STATUS_RECONCILIATION_PENDING) {
            return response()->json(['success' => false, 'message' => 'Only pending review payments can be approved'], 422);
        }

        try {
            $service->approve($payment, (int) $user->id, 'Payment proof approved from admin mobile app.');
        } catch (DuplicateActivePaymentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (QueryException $e) {
            if (PaymentDuplicateGuard::isGuardUniqueIndexViolation($e)) {
                return response()->json(['success' => false, 'message' => 'Active payment already exists for this member/month.'], 422);
            }

            throw $e;
        }

        return response()->json(['success' => true, 'message' => 'Payment approved and posted to ledger']);
    }

    public function reject(Request $request, int $id, UploadedProofService $service): JsonResponse
    {
        $user = $this->requirePermission($request, 'payment.approve');
        if (! $user) {
            return $this->user($request) ? $this->forbidden() : $this->unauthenticated();
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $payment = Payment::find($id);
        if (! $payment) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        if ($payment->status !== Payment::STATUS_RECONCILIATION_PENDING) {
            return response()->json(['success' => false, 'message' => 'Only pending review payments can be rejected'], 422);
        }

        $service->reject($payment, $data['reason'], 'Payment proof rejected from admin mobile app.');

        return response()->json(['success' => true, 'message' => 'Payment rejected']);
    }


    public function methods(Request $request): JsonResponse
    {
        $user = $this->requirePermission($request, 'payments.manual_record_admin');
        if (! $user) {
            return $this->user($request) ? $this->forbidden() : $this->unauthenticated();
        }

        $rows = PaymentMethod::where('is_active', 1)->orderBy('name')->get(['id', 'code', 'name']);

        return response()->json(['success' => true, 'methods' => $rows]);
    }

    public function memberBills(Request $request): JsonResponse
    {
        $user = $this->requirePermission($request, 'payments.manual_record_admin');
        if (! $user) {
            return $this->user($request) ? $this->forbidden() : $this->unauthenticated();
        }

        $data = $request->validate(['member_code' => ['required', 'string', 'max:50']]);

        $member = Member::where('member_code', trim($data['member_code']))->first(['id', 'member_code', 'name']);
        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $bills = Billing::query()
            ->where('member_id', $member->id)
            ->whereDoesntHave('payments', function ($q) {
                $q->whereIn('status', Payment::PAID_STATUSES);
            })
            ->orderByDesc('month_cycle')
            ->orderByDesc('id')
            ->limit(24)
            ->get(['id', 'month_cycle', 'net_payable', 'due_date']);

        $outstanding = (float) (MemberLedger::query()
            ->where('member_id', $member->id)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->value('balance_after') ?? 0);

        return response()->json([
            'success' => true,
            'member_id' => (int) $member->id,
            'member_code' => $member->member_code,
            'member_name' => $member->name,
            'outstanding' => number_format($outstanding, 2, '.', ''),
            'unpaid_bills' => $bills->map(fn ($b) => [
                'bill_id' => (int) $b->id,
                'month_cycle' => $b->month_cycle,
                'net_payable' => number_format((float) $b->net_payable, 2, '.', ''),
                'due_date' => optional($b->due_date)->format('Y-m-d'),
            ]),
        ]);
    }

    public function store(Request $request, ManualPaymentService $service): JsonResponse
    {
        $user = $this->requirePermission($request, 'payments.manual_record_admin');
        if (! $user) {
            return $this->user($request) ? $this->forbidden() : $this->unauthenticated();
        }

        $data = $request->validate([
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'bill_id' => ['required', 'integer', 'exists:billings,id'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['nullable', 'date'],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $payment = $service->post(
                (int) $data['member_id'],
                (int) $data['bill_id'],
                (int) $data['payment_method_id'],
                (float) $data['amount'],
                (int) $user->id,
                [
                    'payment_date' => $data['payment_date'] ?? null,
                    'reference_no' => $data['reference_no'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]
            );
        } catch (DuplicateActivePaymentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (QueryException $e) {
            if (PaymentDuplicateGuard::isGuardUniqueIndexViolation($e)) {
                return response()->json(['success' => false, 'message' => 'Active payment already exists for this member/month.'], 422);
            }

            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment posted to ledger',
            'payment_id' => (int) $payment->id,
            'payment_ref' => $payment->payment_ref,
        ], 201);
    }

    public function outstanding(Request $request): JsonResponse
    {
        $user = $this->requirePermission($request, 'payments.view_admin');
        if (! $user) {
            return $this->user($request) ? $this->forbidden() : $this->unauthenticated();
        }

        $data = $request->validate([
            'member_code' => ['required', 'string', 'max:50'],
        ]);

        $member = Member::where('member_code', trim($data['member_code']))->first(['id', 'member_code', 'name']);
        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $outstanding = (float) (MemberLedger::query()
            ->where('member_id', $member->id)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->value('balance_after') ?? 0);

        $last = Payment::query()
            ->where('member_id', $member->id)
            ->whereIn('status', Payment::PAID_STATUSES)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->first(['amount', 'payment_date', 'method']);

        return response()->json([
            'success' => true,
            'member_code' => $member->member_code,
            'member_name' => $member->name,
            'outstanding' => number_format($outstanding, 2, '.', ''),
            'last_payment' => $last ? [
                'amount' => number_format((float) $last->amount, 2, '.', ''),
                'date' => optional($last->payment_date)->format('Y-m-d'),
                'method' => $last->method,
            ] : null,
        ]);
    }
}
