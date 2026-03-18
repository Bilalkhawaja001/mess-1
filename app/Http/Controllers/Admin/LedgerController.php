<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ledger\StoreLedgerAdjustmentRequest;
use App\Models\Member;
use App\Models\MemberLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LedgerController extends Controller
{
    public function index(Request $request): View
    {
        $memberId = $request->input('member_id');
        $includeFuture = $request->boolean('include_future', false);
        $includeZero = $request->boolean('include_zero', false);

        $members = Member::query()->where('is_active', true)->orderBy('member_code')->get();

        $q = MemberLedger::query()->with('member');
        if ($memberId) {
            $q->where('member_id', (int) $memberId);
        }
        if (! $includeFuture) {
            $q->whereDate('entry_date', '<=', now()->toDateString());
        }
        if (! $includeZero) {
            $q->where(function ($qq) {
                $qq->where('debit', '!=', 0)->orWhere('credit', '!=', 0);
            });
        }

        $rows = $q->orderByDesc('entry_date')->orderByDesc('id')->limit(500)->get();

        if ($memberId) {
            $asc = MemberLedger::query()->where('member_id', (int) $memberId)->orderBy('entry_date')->orderBy('id')->get();
            $bal = 0.0;
            $map = [];
            foreach ($asc as $r) {
                $bal = round($bal + (float) $r->debit - (float) $r->credit, 2);
                $map[$r->id] = $bal;
            }
            foreach ($rows as $r) {
                if (isset($map[$r->id])) {
                    $r->balance_after = $map[$r->id];
                }
            }
        }

        return view('admin.ledger.index', compact('members', 'rows', 'memberId', 'includeFuture', 'includeZero'));
    }

    public function storeAdjustment(StoreLedgerAdjustmentRequest $request): RedirectResponse
    {
        $debit = (float) $request->input('debit', 0);
        $credit = (float) $request->input('credit', 0);

        if ($debit < 0 || $credit < 0 || ($debit == 0.0 && $credit == 0.0)) {
            return redirect()->route('admin.ledger.index', ['member_id' => $request->input('member_id')])
                ->with('error', 'Enter valid debit/credit adjustment.');
        }

        $memberId = (int) $request->input('member_id');

        $lastBal = (float) (MemberLedger::query()
            ->where('member_id', $memberId)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->value('balance_after') ?? 0);

        $newBal = round($lastBal + $debit - $credit, 2);

        MemberLedger::query()->create([
            'member_id' => $memberId,
            'entry_date' => $request->input('entry_date'),
            'debit' => $debit,
            'credit' => $credit,
            'ref_type' => 'ADJUSTMENT',
            'ref_id' => 0,
            'balance_after' => $newBal,
            'reason_code' => $request->input('reason_code', 'MANUAL_ADJUSTMENT'),
            'posted_by_user_id' => Auth::id(),
        ]);

        return redirect()->route('admin.ledger.index', ['member_id' => $memberId])->with('success', 'Ledger adjustment posted.');
    }
}
