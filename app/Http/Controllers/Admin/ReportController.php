<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\Member;
use App\Models\MemberLedger;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $monthCycle = (string) $request->input('month_cycle', '');
        $memberId = (string) $request->input('member_id', '');

        $members = Member::query()->where('is_active', true)->orderBy('member_code')->get();
        $recoveryRows = [];
        $ledgerRows = collect();

        if ($monthCycle !== '') {
            $bills = Billing::query()->with('member')->where('month_cycle', $monthCycle)->get();
            foreach ($bills as $b) {
                $paid = (float) Payment::query()
                    ->where('member_id', $b->member_id)
                    ->where('status', 'APPROVED')
                    ->whereBetween('payment_date', [$monthCycle . '-01', date('Y-m-t', strtotime($monthCycle . '-01'))])
                    ->sum('amount');
                $outstanding = (float) $b->net_payable - $paid;
                $recoveryRows[] = [
                    'member_code' => $b->member->member_code ?? '',
                    'net_payable' => (float) $b->net_payable,
                    'paid' => $paid,
                    'adjustment' => 0,
                    'outstanding' => $outstanding,
                ];
            }
        }

        if ($memberId !== '') {
            $ledgerRows = MemberLedger::query()
                ->with('member')
                ->where('member_id', (int) $memberId)
                ->orderBy('entry_date')
                ->orderBy('id')
                ->get();
        }

        return view('admin.reports.index', compact('monthCycle', 'memberId', 'members', 'recoveryRows', 'ledgerRows'));
    }

    public function overallRecovery(Request $request): View|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $statusFilter = strtoupper((string) $request->input('status', 'ALL'));
        if (!in_array($statusFilter, ['ALL', 'DUE', 'CLEAR', 'ADVANCE'], true)) {
            $statusFilter = 'ALL';
        }
        $q = trim((string) $request->input('q', ''));

        $rows = Member::query()
            ->leftJoin('member_ledgers', 'member_ledgers.member_id', '=', 'members.id')
            ->selectRaw('members.id, members.member_code, members.name, members.department_name, COALESCE(SUM(member_ledgers.debit),0) as total_debit, COALESCE(SUM(member_ledgers.credit),0) as total_credit')
            ->groupBy('members.id', 'members.member_code', 'members.name', 'members.department_name')
            ->orderBy('members.member_code')
            ->get()
            ->map(function ($row) {
                $debit = (float) $row->total_debit;
                $credit = (float) $row->total_credit;
                $closing = $debit - $credit;

                $status = 'Clear';
                if ($closing > 0.0001) {
                    $status = 'Due';
                } elseif ($closing < -0.0001) {
                    $status = 'Advance';
                }

                return [
                    'member_id' => $row->member_code ?? '',
                    'member_name' => $row->name ?? '',
                    'department' => $row->department_name ?? '',
                    'total_debit' => $debit,
                    'total_credit' => $credit,
                    'closing_balance' => $closing,
                    'status' => $status,
                ];
            });

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $rows = $rows->filter(function (array $r) use ($needle) {
                return str_contains(mb_strtolower($r['member_id']), $needle)
                    || str_contains(mb_strtolower($r['member_name']), $needle);
            })->values();
        }

        if ($statusFilter !== 'ALL') {
            $wanted = match ($statusFilter) {
                'DUE' => 'Due',
                'CLEAR' => 'Clear',
                'ADVANCE' => 'Advance',
                default => null,
            };
            if ($wanted) {
                $rows = $rows->where('status', $wanted)->values();
            }
        }

        $totals = [
            'members' => $rows->count(),
            'due_count' => $rows->where('status', 'Due')->count(),
            'clear_count' => $rows->where('status', 'Clear')->count(),
            'advance_count' => $rows->where('status', 'Advance')->count(),
            'total_debit' => (float) $rows->sum('total_debit'),
            'total_credit' => (float) $rows->sum('total_credit'),
            'total_closing' => (float) $rows->sum('closing_balance'),
        ];

        if ((string) $request->input('export', '') === 'csv') {
            $filename = 'overall_recovery_' . now()->format('Ymd_His') . '.csv';
            return Response::streamDownload(function () use ($rows, $totals) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Member ID', 'Member Name', 'Department', 'Total Debit', 'Total Credit', 'Closing Balance', 'Status']);
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r['member_id'],
                        $r['member_name'],
                        $r['department'],
                        number_format($r['total_debit'], 2, '.', ''),
                        number_format($r['total_credit'], 2, '.', ''),
                        number_format($r['closing_balance'], 2, '.', ''),
                        $r['status'],
                    ]);
                }
                fputcsv($out, []);
                fputcsv($out, ['Totals', '', '', number_format($totals['total_debit'], 2, '.', ''), number_format($totals['total_credit'], 2, '.', ''), number_format($totals['total_closing'], 2, '.', ''), '']);
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        return view('admin.reports.overall_recovery', [
            'rows' => $rows,
            'totals' => $totals,
            'statusFilter' => $statusFilter,
            'q' => $q,
        ]);
    }
}
