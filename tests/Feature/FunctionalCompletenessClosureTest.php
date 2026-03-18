<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class FunctionalCompletenessClosureTest extends TestCase
{
    public function test_required_module_route_surface_exists(): void
    {
        $routes = file_get_contents(__DIR__.'/../../routes/web.php');
        $this->assertStringContainsString("admin.inventory.index", $routes);
        $this->assertStringContainsString("admin.procurement.index", $routes);
        $this->assertStringContainsString("admin.kitchen.index", $routes);
        $this->assertStringContainsString("admin.guests.index", $routes);
        $this->assertStringContainsString("admin.accounting.index", $routes);
        $this->assertStringContainsString("admin.exports.index", $routes);
    }

    public function test_mandatory_p0_routes_still_present(): void
    {
        $routes = file_get_contents(__DIR__.'/../../routes/web.php');
        $this->assertStringContainsString("admin.month.hard-reset", $routes);
        $this->assertStringContainsString("admin.ledger.import", $routes);
        $this->assertStringContainsString("admin.ledger.recompute", $routes);
        $this->assertStringContainsString("admin.billing.correct", $routes);
    }
}
