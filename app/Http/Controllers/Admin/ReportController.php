<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\Department;
use App\Models\Member;
use App\Models\MemberLedger;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
            $paidStatuses = [
                Payment::STATUS_APPROVED,
                Payment::STATUS_SUCCESS,
                Payment::STATUS_RECONCILIATION_PENDING,
                Payment::STATUS_RECONCILED,
            ];

            foreach ($bills as $b) {
                $paid = (float) Payment::query()
                    ->where('member_id', $b->member_id)
                    ->whereIn('status', $paidStatuses)
                    ->whereBetween('payment_date', [$monthCycle . '-01', date('Y-m-t', strtotime($monthCycle . '-01'))])
                    ->sum('amount');
                $outstanding = round((float) $b->net_payable - $paid, 2);
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

    public function billsDownload(Request $request): View
    {
        $monthCycle = trim((string) $request->input('month_cycle', ''));
        $groupByDepartment = (string) $request->input('group_by_department', '1') === '1';
        $departmentId = $request->filled('department') ? (int) $request->input('department') : null;
        $separateFiles = (string) $request->input('separate_files', '0') === '1';
        $messBucket = $this->normalizeMessBucket((string) ($request->input('mess') ?: $request->input('mess_bucket', '')));

        $rows = [];
        $grouped = [];
        $totals = null;

        if ($monthCycle !== '') {
            [$rows, $totals] = $this->billsDownloadRows($monthCycle, true, $departmentId, $messBucket);
            $grouped = $groupByDepartment ? $this->groupBillsRows($rows) : [];
        }

        $departments = Department::query()->orderBy('code')->get();

        return view('admin.reports.bills_download', compact(
            'monthCycle',
            'rows',
            'grouped',
            'totals',
            'groupByDepartment',
            'departmentId',
            'separateFiles',
            'departments',
            'messBucket',
        ));
    }

    public function billsDownloadExportCsv(Request $request): RedirectResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $monthCycle = trim((string) $request->input('month_cycle', ''));
        if ($monthCycle === '') {
            return redirect()->route('admin.reports.bills-download')->with('warning', 'month_cycle is required');
        }

        $departmentId = $request->filled('department') ? (int) $request->input('department') : null;
        $separateFiles = (string) $request->input('separate_files', '0') === '1';
        $messBucket = $this->normalizeMessBucket((string) ($request->input('mess') ?: $request->input('mess_bucket', '')));

        [$rows] = $this->billsDownloadRows($monthCycle, true, $departmentId, $messBucket);
        if (empty($rows)) {
            return redirect()->route('admin.reports.bills-download', ['month_cycle' => $monthCycle])->with('warning', 'No data found');
        }

        $headers = $this->billsDownloadExportHeaders();
        $grouped = $this->groupBillsRows($rows);

        if ($separateFiles) {
            $tmpZip = tempnam(sys_get_temp_dir(), 'bills_zip_');
            $zipPath = $tmpZip . '.zip';
            @unlink($tmpZip);

            $zip = new \ZipArchive();
            $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            foreach ($grouped as $group) {
                $handle = fopen('php://temp', 'r+');
                fputcsv($handle, $headers);
                foreach ($group['rows'] as $row) {
                    fputcsv($handle, $this->billsDownloadExportRow($row));
                }
                rewind($handle);
                $safeName = str_replace(['/', '\\'], '-', (string) $group['department']);
                $zip->addFromString("bills_{$monthCycle}_{$safeName}.csv", stream_get_contents($handle));
                fclose($handle);
            }

            $zip->close();

            return response()->download($zipPath, "bills_download_{$monthCycle}_by_department.zip", ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true);
        }

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $this->billsDownloadExportRow($row));
            }
            fclose($out);
        }, "bills_download_{$monthCycle}.csv", ['Content-Type' => 'text/csv']);
    }

    public function billsDownloadExportXlsx(Request $request): RedirectResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $monthCycle = trim((string) $request->input('month_cycle', ''));
        if ($monthCycle === '') {
            return redirect()->route('admin.reports.bills-download')->with('warning', 'month_cycle is required');
        }

        $departmentId = $request->filled('department') ? (int) $request->input('department') : null;
        $separateFiles = (string) $request->input('separate_files', '0') === '1';
        $messBucket = $this->normalizeMessBucket((string) ($request->input('mess') ?: $request->input('mess_bucket', '')));

        [$rows] = $this->billsDownloadRows($monthCycle, true, $departmentId, $messBucket);
        if (empty($rows)) {
            return redirect()->route('admin.reports.bills-download', ['month_cycle' => $monthCycle])->with('warning', 'No data found');
        }

        $grouped = $this->groupBillsRows($rows);
        $headers = $this->billsDownloadExportHeaders();

        return response()->streamDownload(function () use ($rows, $grouped, $separateFiles, $headers) {
            $spreadsheet = new Spreadsheet();

            $writeSheet = function ($sheet, array $sheetRows) use ($headers) {
                $sheet->fromArray($headers, null, 'A1');
                $rowIndex = 2;
                foreach ($sheetRows as $row) {
                    $sheet->fromArray($this->billsDownloadExportRow($row), null, 'A' . $rowIndex);
                    $rowIndex++;
                }
            };

            if ($separateFiles) {
                $spreadsheet->removeSheetByIndex(0);
                foreach ($grouped as $group) {
                    $sheet = $spreadsheet->createSheet();
                    $sheet->setTitle(mb_substr((string) ($group['department'] ?: 'NoDept'), 0, 31));
                    $writeSheet($sheet, $group['rows']);
                }
            } else {
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('Summary');
                $writeSheet($sheet, $rows);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, "bills_download_{$monthCycle}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function billsDownloadRows(string $monthCycle, bool $includePrevNonzero = true, ?int $departmentId = null, ?string $messBucket = null): array
    {
        $month = Carbon::createFromFormat('Y-m', $monthCycle);
        $start = $month->copy()->startOfMonth()->toDateString();
        $end = $month->copy()->endOfMonth()->toDateString();

        $billedMembers = Billing::query()
            ->where('month_cycle', $monthCycle)
            ->distinct()
            ->pluck('member_id')
            ->all();

        $ledgerMembers = MemberLedger::query()
            ->whereBetween('entry_date', [$start, $end])
            ->distinct()
            ->pluck('member_id')
            ->all();

        $prevNonzeroMembers = [];
        if ($includePrevNonzero) {
            $prevNonzeroMembers = MemberLedger::query()
                ->selectRaw('member_id, COALESCE(SUM(debit - credit), 0) as balance')
                ->whereDate('entry_date', '<', $start)
                ->groupBy('member_id')
                ->get()
                ->filter(fn ($row) => round((float) $row->balance, 2) !== 0.0)
                ->pluck('member_id')
                ->all();
        }

        $targetMembers = array_values(array_unique(array_merge($billedMembers, $ledgerMembers, $prevNonzeroMembers)));
        sort($targetMembers);

        $members = Member::query()->with('mess.department')->whereIn('id', $targetMembers)->get()->keyBy('id');
        $rows = [];
        $totals = [
            'total_days' => 0,
            'rate_per_day' => 0.0,
            'previous_balance' => 0.0,
            'current_expenses' => 0.0,
            'payable' => 0.0,
        ];

        foreach ($targetMembers as $memberId) {
            $member = $members->get($memberId);
            if (! $member) {
                continue;
            }

            $dept = $member->mess?->department;
            $deptKey = $dept?->id;
            if ($departmentId && $deptKey !== $departmentId) {
                continue;
            }

            $departmentCode = $dept?->code ?: ($member->department_name ?: '');
            $messCode = strtoupper((string) ($member->mess?->code ?: $member->mess?->name ?: ''));
            if (! $this->messMatches($messCode, $messBucket)) {
                continue;
            }

            $previousBalance = round((float) (MemberLedger::query()
                ->where('member_id', $memberId)
                ->whereDate('entry_date', '<', $start)
                ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
                ->value('balance')), 2);

            $billAgg = Billing::query()
                ->where('member_id', $memberId)
                ->where('month_cycle', $monthCycle)
                ->selectRaw('COALESCE(SUM(net_payable), 0) as current_expenses, COALESCE(SUM(active_days), 0) as total_days')
                ->first();

            $billRow = Billing::query()
                ->where('member_id', $memberId)
                ->where('month_cycle', $monthCycle)
                ->latest('id')
                ->first();

            $currentExpenses = round((float) ($billAgg?->current_expenses ?? 0), 2);
            $totalDays = (int) ($billAgg?->total_days ?? 0);
            $ratePerDay = round((float) ($billRow?->rate_per_day ?? 0), 2);
            $payable = round($previousBalance + $currentExpenses, 2);

            $row = [
                'month_cycle' => $monthCycle,
                'member_id' => $member->member_code,
                'member_name' => $member->name,
                'department' => $departmentCode,
                'department_id' => $deptKey,
                'mess_name' => $messCode,
                'total_days' => $totalDays,
                'rate_per_day' => $ratePerDay,
                'previous_balance' => $previousBalance,
                'current_expenses' => $currentExpenses,
                'payable' => $payable,
                'member_pk' => $memberId,
            ];

            $rows[] = $row;
            $totals['total_days'] += $totalDays;
            $totals['rate_per_day'] = round($totals['rate_per_day'] + $ratePerDay, 2);
            $totals['previous_balance'] = round($totals['previous_balance'] + $previousBalance, 2);
            $totals['current_expenses'] = round($totals['current_expenses'] + $currentExpenses, 2);
            $totals['payable'] = round($totals['payable'] + $payable, 2);
        }

        usort($rows, fn (array $a, array $b) => [$a['department'] ?: 'ZZZ', $a['member_id']] <=> [$b['department'] ?: 'ZZZ', $b['member_id']]);

        return [$rows, $totals, $start, $end];
    }

    private function groupBillsRows(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $key = $row['department'] ?: '(No Department)';
            $groups[$key][] = $row;
        }

        ksort($groups);
        $output = [];

        foreach ($groups as $department => $items) {
            usort($items, fn (array $a, array $b) => $a['member_id'] <=> $b['member_id']);
            $output[] = [
                'department' => $department,
                'rows' => $items,
                'totals' => [
                    'member_count' => count($items),
                    'total_days' => array_sum(array_column($items, 'total_days')),
                    'rate_per_day' => round(array_sum(array_column($items, 'rate_per_day')), 2),
                    'previous_balance' => round(array_sum(array_column($items, 'previous_balance')), 2),
                    'current_expenses' => round(array_sum(array_column($items, 'current_expenses')), 2),
                    'payable' => round(array_sum(array_column($items, 'payable')), 2),
                ],
            ];
        }

        return $output;
    }

    private function normalizeMessBucket(string $messBucket): ?string
    {
        $messBucket = strtoupper(trim($messBucket));
        if ($messBucket === '') {
            return null;
        }

        return match ($messBucket) {
            'CENTRALIZE', 'CENTRAL' => 'CENTRALIZED',
            'EXEC' => 'EXECUTIVE',
            'CONTRACTOR' => 'CONTRACTORS',
            default => $messBucket,
        };
    }

    private function messMatches(string $messCode, ?string $messBucket): bool
    {
        if ($messBucket === null || $messBucket === '') {
            return true;
        }

        return strtoupper($messCode) === strtoupper($messBucket);
    }

    private function billsDownloadExportHeaders(): array
    {
        return ['Month', 'EmployeeID', 'Name', 'Department', 'Mess', 'Days', 'Rate per Day', 'Current Bill', 'Previous', 'Payable'];
    }

    private function billsDownloadExportRow(array $row): array
    {
        return [
            Carbon::createFromFormat('Y-m', $row['month_cycle'])->format('M-Y'),
            $row['member_id'],
            $row['member_name'],
            $row['department'],
            $row['mess_name'],
            (int) ($row['total_days'] ?? 0),
            round((float) ($row['rate_per_day'] ?? 0), 2),
            round((float) ($row['current_expenses'] ?? 0), 2),
            round((float) ($row['previous_balance'] ?? 0), 2),
            round((float) ($row['payable'] ?? 0), 2),
        ];
    }
}
