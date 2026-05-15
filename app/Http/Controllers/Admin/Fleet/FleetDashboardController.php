<?php

namespace App\Http\Controllers\Admin\Fleet;

use App\Http\Controllers\Controller;
use App\Services\Fleet\FleetAlertService;
use App\Services\Fleet\FleetKpiService;

class FleetDashboardController extends Controller
{
    public function __construct(
        private FleetKpiService $kpis,
        private FleetAlertService $alerts
    ) {}

    public function index()
    {
        return view('admin.fleet.dashboard.index', [
            'kpis' => $this->kpis->dashboard(),
            'alerts' => $this->alerts->dashboardAlerts(),
            'fuelTrend' => $this->kpis->monthlyFuelTrend(),
            'costTrend' => $this->kpis->monthlyMaintenanceTrend(),
            'statusBoard' => $this->kpis->vehicleStatusBreakdown(),
            'maintenanceDue' => $this->kpis->maintenanceDueBoard(),
            'documentExpiry' => $this->kpis->documentExpiryPanel(),
            'driverPerformance' => $this->kpis->driverPerformanceTable(),
            'availability' => $this->kpis->vehicleAvailabilityBoard(),
            'quickActions' => $this->kpis->quickActions(),
            'reportActions' => $this->kpis->reportActions(),
        ]);
    }
}
