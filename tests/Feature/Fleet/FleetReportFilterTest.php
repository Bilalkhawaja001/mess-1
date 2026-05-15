<?php

namespace Tests\Feature\Fleet;

use App\Services\Fleet\FleetReportService;
use Tests\TestCase;

class FleetReportFilterTest extends TestCase
{
    public function test_report_service_resolves(): void
    {
        $this->assertInstanceOf(FleetReportService::class, app(FleetReportService::class));
    }
}
