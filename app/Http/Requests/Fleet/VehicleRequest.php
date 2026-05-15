<?php

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_no' => 'required|max:50',
            'vehicle_type_id' => 'nullable|integer',
            'make' => 'nullable|max:100',
            'model' => 'nullable|max:100',
            'year' => 'nullable|integer|min:1950|max:2100',
            'registration_no' => 'nullable|max:100',
            'assigned_driver_id' => 'nullable|integer',
            'current_odometer' => 'required|numeric|min:0',
            'fuel_type' => 'required|in:Petrol,Diesel,CNG,Hybrid,Electric',
            'status' => 'required|in:Active,Inactive,Under Maintenance,Sold'
        ];
    }
}
