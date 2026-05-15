<?php

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

class FuelLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => 'required|exists:fleet_vehicles,id',
            'driver_id' => 'nullable|exists:fleet_drivers,id',
            'fuel_date' => 'required|date',
            'fuel_station' => 'nullable|max:150',
            'fuel_type' => 'required|in:Petrol,Diesel,CNG,Hybrid,Electric',
            'liters' => 'required|numeric|min:0.01',
            'rate_per_liter' => 'required|numeric|min:0',
            'odometer_reading' => 'required|numeric|min:0',
            'previous_odometer' => 'nullable|numeric|min:0'
        ];
    }
}
