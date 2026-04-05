<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class FunctionalCompletenessClosureTest extends TestCase
{
    public function test_required_module_route_surface_exists(): void
    {
        $routes = file_get_contents(__DIR__.'/../../routes/web.php');
        $this->assertStringContainsString("->name('inventory.index')", $routes);
        $this->assertStringContainsString("->name('procurement.index')", $routes);
        $this->assertStringContainsString("->name('kitchen.index')", $routes);
        $this->assertStringContainsString("->name('guests.index')", $routes);
        $this->assertStringContainsString("->name('accounting.index')", $routes);
        $this->assertStringContainsString("->name('exports.index')", $routes);
    }

    public function test_mandatory_p0_routes_still_present(): void
    {
        $routes = file_get_contents(__DIR__.'/../../routes/web.php');
        $this->assertStringContainsString("->name('month.hard-reset')", $routes);
        $this->assertStringContainsString("->name('ledger.import')", $routes);
        $this->assertStringContainsString("->name('ledger.recompute')", $routes);
        $this->assertStringContainsString("->name('billing.correct')", $routes);
    }
}
