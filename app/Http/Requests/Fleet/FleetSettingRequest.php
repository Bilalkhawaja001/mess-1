<?php
namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

class FleetSettingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.alert_days' => ['required', 'integer', 'min:1', 'max:365'],
            'settings.fuel_average_min_km_per_liter' => ['required', 'numeric', 'min:0'],
            'settings.fuel_average_max_km_per_liter' => ['required', 'numeric', 'gt:settings.fuel_average_min_km_per_liter'],
            'settings.maintenance_due_km_buffer' => ['required', 'integer', 'min:0'],
            'settings.maintenance_due_days_buffer' => ['required', 'integer', 'min:0'],
        ];
    }
}
