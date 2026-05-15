<?php

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

class MaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => 'required|exists:fleet_vehicles,id',
            'maintenance_date' => 'required|date',
            'odometer_reading' => 'required|numeric|min:0',
            'maintenance_type' => 'required|max:100',
            'workshop' => 'nullable|max:150',
            'parts_cost' => 'nullable|numeric|min:0',
            'labour_cost' => 'nullable|numeric|min:0',
            'next_service_odometer' => 'nullable|numeric|min:0',
            'next_service_date' => 'nullable|date',
            'status' => 'required|in:Pending,In Progress,Completed'
        ];
    }
}
