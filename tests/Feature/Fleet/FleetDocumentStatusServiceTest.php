<?php

namespace Tests\Unit\Fleet;

use App\Services\Fleet\FleetDocumentStatusService;
use Tests\TestCase;

class FleetDocumentStatusServiceTest extends TestCase
{
    public function test_statuses(): void
    {
        $svc = new FleetDocumentStatusService();
        $this->assertSame('expired', $svc->status(now()->subDay()->toDateString()));
        $this->assertSame('expiring_soon', $svc->status(now()->addDays(10)->toDateString()));
        $this->assertSame('valid', $svc->status(now()->addDays(60)->toDateString()));
    }
}
