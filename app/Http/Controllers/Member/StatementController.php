<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MemberLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StatementController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = Auth::user();
        $member = $user?->resolvedMemberProfile();

        if (! $member) {
            return redirect()->route('member.dashboard')->with('warning', 'Your member profile is not linked yet. Please contact admin.');
        }

        $ledgerRows = MemberLedger::query()
            ->where('member_id', $member->id)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $rows = $ledgerRows->map(function (MemberLedger $row) {
            return (object) [
                'date' => optional($row->entry_date)?->format('Y-m-d') ?? '-',
                'description' => $this->buildDescription($row),
                'debit' => (float) $row->debit,
                'credit' => (float) $row->credit,
                'running_balance' => (float) $row->balance_after,
            ];
        });

        $outstandingAmount = (float) ($ledgerRows->last()->balance_after ?? 0);

        return view('member.statement.index', [
            'member' => $member,
            'rows' => $rows,
            'outstandingAmount' => $outstandingAmount,
        ]);
    }

    private function buildDescription(MemberLedger $row): string
    {
        $parts = [];

        if (! empty($row->ref_type)) {
            $parts[] = strtoupper((string) $row->ref_type);
        }

        if (! empty($row->ref_id)) {
            $parts[] = '#'.$row->ref_id;
        }

        if (! empty($row->reason_code)) {
            $parts[] = str_replace('_', ' ', (string) $row->reason_code);
        }

        if ($row->is_opening_balance) {
            $parts[] = 'Opening Balance';
        }

        return $parts !== [] ? implode(' - ', $parts) : 'Ledger Entry';
    }
}
