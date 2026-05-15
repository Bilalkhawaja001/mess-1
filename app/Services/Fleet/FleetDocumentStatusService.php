<?php

namespace App\Services\Fleet;

use Carbon\Carbon;

class FleetDocumentStatusService
{
    public function status(string $expiryDate, int $alertDays = 30): string
    {
        $expiry = Carbon::parse($expiryDate)->startOfDay();
        $today = now()->startOfDay();

        if ($expiry->lt($today)) return 'expired';
        if ($expiry->lte($today->copy()->addDays($alertDays))) return 'expiring_soon';
        return 'valid';
    }
}
