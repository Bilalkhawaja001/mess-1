<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LedgerToolchainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LedgerToolchainController extends Controller
{
    public function __construct(private readonly LedgerToolchainService $ledgerToolchainService)
    {
    }

    public function importLedger(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt']]);
        $count = $this->ledgerToolchainService->importOpeningBalances($request->file('file'), (int) Auth::id());

        return redirect()->route('admin.ledger.index')->with('success', "Opening balances imported: {$count}");
    }

    public function recompute(Request $request): RedirectResponse
    {
        $payload = $request->validate(['member_id' => ['nullable', 'integer']]);
        $updated = $this->ledgerToolchainService->recompute(isset($payload['member_id']) ? (int) $payload['member_id'] : null);

        return redirect()->route('admin.ledger.index', ['member_id' => $payload['member_id'] ?? null])->with('success', "Ledger recomputed rows: {$updated}");
    }
}
