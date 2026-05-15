<?php

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

class TripLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => 'required|exists:fleet_vehicles,id',
            'driver_id' => 'required|exists:fleet_drivers,id',
            'trip_date' => 'required|date',
            'from_location' => 'required|max:150',
            'to_location' => 'required|max:150',
            'purpose' => 'nullable|max:255',
            'start_odometer' => 'required|numeric|min:0',
            'end_odometer' => 'required|numeric|gte:start_odometer'
        ];
    }
}
