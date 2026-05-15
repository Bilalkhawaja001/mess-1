<?php

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

class TyreBatteryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:fleet_vehicles,id'],
            'item_type' => ['required', 'in:tyre,battery'],
            'brand' => ['nullable', 'string', 'max:120'],
            'serial_no' => ['nullable', 'string', 'max:120'],
            'installed_at' => ['nullable', 'date'],
            'installed_odometer' => ['nullable', 'numeric', 'min:0'],
            'removed_at' => ['nullable', 'date', 'after_or_equal:installed_at'],
            'removed_odometer' => ['nullable', 'numeric', 'gte:installed_odometer'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:Active,Removed,Failed,Warranty Claim'],
        ];
    }
}
