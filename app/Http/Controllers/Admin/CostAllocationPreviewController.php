<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Billing\CostAllocationPreviewService;
use App\Support\BusinessMonthCycle;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CostAllocationPreviewController extends Controller
{
    public function index(Request $request, CostAllocationPreviewService $service): View
    {
        $monthCycle = trim((string) $request->query('month_cycle', BusinessMonthCycle::defaultDashboardMonthCycle()));
        $execWeight = (float) $request->query('executive_weight', 1.25);
        $centWeight = (float) $request->query('centralized_weight', 1.0);

        if ($execWeight <= 0) $execWeight = 1.25;
        if ($centWeight <= 0) $centWeight = 1.0;

        $data = $service->build($monthCycle, $execWeight, $centWeight);

        return view('admin.cost_allocation_preview.index', $data);
    }
}
