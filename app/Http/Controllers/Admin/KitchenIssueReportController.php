<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\KitchenIssueReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KitchenIssueReportController extends Controller
{
    public function __construct(private readonly KitchenIssueReportService $reportService)
    {
    }

    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $report = $this->reportService->build($filters['from_date'], $filters['to_date'], $filters['item_id']);
        $items = $this->reportService->itemOptions();

        return view('admin.kitchen.reports.issues', [
            'filters' => $filters,
            'rows' => $report['rows'],
            'totals' => $report['totals'],
            'items' => $items,
            'rateSource' => $report['rate_source'],
            'rateFormula' => $report['rate_formula'],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $report = $this->reportService->build($filters['from_date'], $filters['to_date'], $filters['item_id']);

        $filename = sprintf(
            'approved-kitchen-issue-report-%s-to-%s.csv',
            $filters['from_date'],
            $filters['to_date']
        );

        return response()->streamDownload(function () use ($filters, $report) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Approved Kitchen Issue Report']);
            fputcsv($handle, ['From Date', $filters['from_date']]);
            fputcsv($handle, ['To Date', $filters['to_date']]);
            fputcsv($handle, []);
            fputcsv($handle, ['Item ID', 'SKU', 'Item', 'UOM', 'Total Issued Qty', 'Issue Count', 'Estimated Avg Rate', 'Estimated Amount']);

            foreach ($report['rows'] as $row) {
                fputcsv($handle, [
                    $row['item_id'],
                    $row['sku'],
                    $row['item_name'],
                    $row['uom'],
                    number_format($row['total_issued_qty'], 3, '.', ''),
                    $row['issue_count'],
                    number_format($row['avg_rate'], 2, '.', ''),
                    number_format($row['estimated_amount'], 2, '.', ''),
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Totals', '', '', '', '', '', '', number_format($report['totals']['grand_total_amount'], 2, '.', '')]);
            fputcsv($handle, ['Total Unique Items', $report['totals']['total_unique_items']]);
            fputcsv($handle, ['Total Approved Issue Rows', $report['totals']['total_approved_issue_rows']]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'item_id' => 'nullable|integer|exists:items,id',
        ]);

        return [
            'from_date' => $validated['from_date'],
            'to_date' => $validated['to_date'],
            'item_id' => isset($validated['item_id']) ? (int) $validated['item_id'] : null,
        ];
    }
}
