<?php

namespace App\Http\Controllers\Admin\Fleet;

use App\Http\Controllers\Controller;
use App\Services\Fleet\FleetReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FleetReportController extends Controller
{
    public function __construct(private FleetReportService $reports) {}

    public function index(Request $request)
    {
        $filters = $request->only(['from', 'to', 'vehicle_id', 'driver_id', 'status', 'category', 'document_status']);

        return view('admin.fleet.reports.index', [
            'summary' => $this->reports->summary($filters),
            'catalog' => $this->reports->catalog($filters),
            'filters' => $this->reports->filters(),
            'exportColumns' => $this->reports->exportColumns(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $report = $request->string('report', 'vehicle_fuel_average')->toString();
        $filters = $request->only(['from', 'to', 'vehicle_id', 'driver_id', 'status', 'category', 'document_status']);
        $columns = $this->reports->exportColumns()[$report] ?? [];
        $rows = $this->reports->exportRows($report, $filters);
        abort_if(empty($columns), 404, 'Unknown fleet report.');

        return response()->streamDownload(function () use ($columns, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
            foreach ($rows as $row) {
                $line = [];
                foreach ($columns as $column) {
                    $line[] = data_get($row, $column);
                }
                fputcsv($out, $line);
            }
            fclose($out);
        }, 'fleet-report-' . $report . '-' . now()->format('YmdHis') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
