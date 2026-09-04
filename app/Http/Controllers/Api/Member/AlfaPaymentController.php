<?php
namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Api\MemberApiController;
use App\Services\Payments\AlfaPaymentSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Online payment endpoints for the member app.
 *
 * Provider is disabled until Bank Alfalah approval, so every endpoint
 * returns a controlled "unavailable" response. No fees are calculated,
 * no bill is modified, and no payment row is created.
 */
class AlfaPaymentController extends MemberApiController
{
    public function __construct(private AlfaPaymentSettings $settings)
    {
        parent::__construct();
    }

    /** GET /api/member/payments/options */
    public function options(Request $request): JsonResponse
    {
        if (! ($row = $this->memberFromToken($request))) {
            return $this->unauthenticated();
        }

        $available = $this->settings->providerEnabled();

        return response()->json([
            'success' => true,
            'payment_available' => $available,
            'available_methods' => $this->settings->availableMethods(),
            'currency' => 'PKR',
            'message' => $available ? '' : $this->settings->unavailableMessage(),
        ]);
    }

    /**
     * POST /api/member/payments/preview
     * Fee/tax figures are pending Bank Alfalah confirmation, so no
     * amount other than the member's own bill is ever returned.
     */
    public function preview(Request $request): JsonResponse
    {
        if (! ($row = $this->memberFromToken($request))) {
            return $this->unauthenticated();
        }

        if (! $this->settings->providerEnabled()) {
            return response()->json([
                'success' => false,
                'payment_available' => false,
                'currency' => 'PKR',
                'message' => $this->settings->unavailableMessage(),
            ], 503);
        }

        return response()->json([
            'success' => false,
            'message' => 'Payment charge breakdown is not configured yet.',
        ], 503);
    }

    /** POST /api/member/payments/create */
    public function create(Request $request): JsonResponse
    {
        if (! ($row = $this->memberFromToken($request))) {
            return $this->unauthenticated();
        }

        return response()->json([
            'success' => false,
            'payment_available' => false,
            'message' => $this->settings->unavailableMessage(),
        ], 503);
    }

    /**
     * GET /api/member/payments/{transaction}/status
     * Read-only. Status is never changed by a client request.
     */
    public function status(Request $request, string $transaction): JsonResponse
    {
        if (! ($row = $this->memberFromToken($request))) {
            return $this->unauthenticated();
        }

        $txn = DB::table('payment_transactions')
            ->where('internal_ref', $transaction)
            ->where('member_id', $row->member_id)   // ownership check
            ->first(['internal_ref', 'status', 'amount', 'currency', 'verified_at']);

        if (! $txn) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        return response()->json([
            'success' => true,
            'reference' => $txn->internal_ref,
            'status' => $txn->status,
            'amount' => $this->apiMoney($txn->amount),
            'currency' => $txn->currency,
            'verified' => $txn->verified_at !== null,
        ]);
    }
}
