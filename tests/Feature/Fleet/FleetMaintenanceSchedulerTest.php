<?php

namespace Tests\Unit\Fleet;

use App\Services\Fleet\FleetMaintenanceScheduler;
use Tests\TestCase;

class FleetMaintenanceSchedulerTest extends TestCase
{
    public function test_due_status_by_date_and_odometer(): void
    {
        $svc = new FleetMaintenanceScheduler();
        $this->assertSame('overdue', $svc->dueStatus(now()->subDay()->toDateString(), null, null));
        $this->assertSame('overdue', $svc->dueStatus(null, 10000, 10001));
        $this->assertSame('due_soon', $svc->dueStatus(now()->addDays(5)->toDateString(), null, null));
        $this->assertSame('ok', $svc->dueStatus(now()->addDays(60)->toDateString(), 10000, 8000));
    }
}
