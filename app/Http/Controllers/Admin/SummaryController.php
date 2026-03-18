<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Summary\SummaryFilterRequest;
use App\Models\Billing;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SummaryController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function index(SummaryFilterRequest $request): View|StreamedResponse
    {
        $monthCycle = (string)$request->input('month_cycle', '');
        $records = collect();
        $total = 0;

        if ($monthCycle !== '') {
            $records = Billing::query()->with('member')->where('month_cycle', $monthCycle)->orderBy('member_id')->get();
            $total = (float)$records->sum('net_payable');
        }

        if ($request->input('export') === 'csv') {
            $this->auditLogService->log('summary.export.csv', Billing::class, null, [], [
                'month_cycle' => $monthCycle,
                'rows' => $records->count(),
                'requested_by' => Auth::id(),
            ]);

            $filename = 'summary_'.$monthCycle.'.csv';
            return response()->streamDownload(function() use ($records, $total) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Member Code','Member Name','Net Payable']);
                foreach ($records as $r) { fputcsv($out, [$r->member->member_code ?? '', $r->member->name ?? '', $r->net_payable]); }
                fputcsv($out, []); fputcsv($out, ['TOTAL','',$total]); fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        if ($request->input('export') === 'xlsx') {
            $this->auditLogService->log('summary.export.xlsx', Billing::class, null, [], [
                'month_cycle' => $monthCycle,
                'rows' => $records->count(),
                'requested_by' => Auth::id(),
            ]);

            $filename = 'summary_'.$monthCycle.'.xlsx';
            return response()->streamDownload(function () use ($records, $total) {
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setCellValue('A1', 'Member Code');
                $sheet->setCellValue('B1', 'Member Name');
                $sheet->setCellValue('C1', 'Net Payable');

                $row = 2;
                foreach ($records as $r) {
                    $sheet->setCellValue('A'.$row, (string) ($r->member->member_code ?? ''));
                    $sheet->setCellValue('B'.$row, (string) ($r->member->name ?? ''));
                    $sheet->setCellValue('C'.$row, (float) $r->net_payable);
                    $row++;
                }

                $row++;
                $sheet->setCellValue('A'.$row, 'TOTAL');
                $sheet->setCellValue('C'.$row, $total);

                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        return view('admin.summary.index', compact('monthCycle','records','total'));
    }
}
