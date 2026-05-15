<?php

namespace App\Services\Fleet;

use App\Models\Fleet\FleetFuelLog;
use App\Models\Fleet\FleetVehicle;
use Illuminate\Validation\ValidationException;

class FleetFuelCalculator
{
    public function prepareFuelLog(array $data, ?int $ignoreLogId = null): array
    {
        $vehicle = FleetVehicle::findOrFail($data['vehicle_id']);

        $previous = FleetFuelLog::where('vehicle_id', $vehicle->id)
            ->when($ignoreLogId, fn($q) => $q->where('id', '!=', $ignoreLogId))
            ->latest('fuel_date')
            ->latest('id')
            ->first();

        $previousOdometer = $data['previous_odometer']
            ?? $previous?->odometer_reading
            ?? $vehicle->current_odometer
            ?? 0;

        if ($data['odometer_reading'] < $previousOdometer) {
            throw ValidationException::withMessages([
                'odometer_reading' => 'Current odometer cannot be less than previous odometer.'
            ]);
        }

        if (($data['liters'] ?? 0) <= 0) {
            throw ValidationException::withMessages(['liters' => 'Liters must be greater than zero.']);
        }

        $distance = $data['odometer_reading'] - $previousOdometer;
        $average = $distance > 0 ? round($distance / $data['liters'], 2) : 0;
        $total = round($data['liters'] * $data['rate_per_liter'], 2);

        $data['previous_odometer'] = $previousOdometer;
        $data['distance'] = $distance;
        $data['average_km_per_liter'] = $average;
        $data['total_amount'] = $total;
        $data['is_abnormal_average'] = $this->isAbnormalAverage($vehicle->fuel_type, $average, $distance);

        return $data;
    }

    public function isAbnormalAverage(?string $fuelType, float $average, int|float $distance): bool
    {
        if ($distance <= 0) return false;
        $min = 2;
        $max = match (strtolower((string) $fuelType)) {
            'diesel' => 18,
            'petrol' => 22,
            'cng' => 35,
            'hybrid' => 40,
            default => 25,
        };
        return $average < $min || $average > $max;
    }
}
