<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberLedger;
use Illuminate\Http\UploadedFile;

class LedgerToolchainService
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function importOpeningBalances(UploadedFile $file, int $userId): int
    {
        $rows = array_map('str_getcsv', file($file->getRealPath()));
        $inserted = 0;

        foreach (array_slice($rows, 1) as $line) {
            $memberCode = trim((string) ($line[0] ?? ''));
            $amount = (float) ($line[1] ?? 0);
            $member = Member::query()->where('member_code', $memberCode)->first();
            if (! $member) {
                continue;
            }

            MemberLedger::query()->create([
                'member_id' => $member->id,
                'entry_date' => now()->toDateString(),
                'debit' => max($amount, 0),
                'credit' => $amount < 0 ? abs($amount) : 0,
                'ref_type' => 'OPENING_BALANCE',
                'ref_id' => 0,
                'balance_after' => $amount,
                'reason_code' => 'OPENING_IMPORT',
                'is_opening_balance' => true,
                'posted_by_user_id' => $userId,
            ]);
            $inserted++;
        }

        $this->auditLogService->log('ledger.opening_imported', MemberLedger::class, null, [], ['rows' => $inserted]);

        return $inserted;
    }

    public function recompute(?int $memberId = null): int
    {
        $query = MemberLedger::query()->orderBy('member_id')->orderBy('entry_date')->orderBy('id');
        if ($memberId) {
            $query->where('member_id', $memberId);
        }

        $rows = $query->get();
        $balances = [];
        $updated = 0;

        foreach ($rows as $row) {
            $mid = (int) $row->member_id;
            $balances[$mid] = round(($balances[$mid] ?? 0) + (float) $row->debit - (float) $row->credit, 2);
            $row->balance_after = $balances[$mid];
            $row->save();
            $updated++;
        }

        $this->auditLogService->log('ledger.recomputed', MemberLedger::class, null, [], ['rows' => $updated]);

        return $updated;
    }
}
