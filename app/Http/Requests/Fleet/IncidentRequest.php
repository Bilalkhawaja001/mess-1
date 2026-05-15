<?php
namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

class IncidentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required','integer','exists:fleet_vehicles,id'],
            'driver_id' => ['nullable','integer','exists:fleet_drivers,id'],
            'incident_date' => ['required','date'],
            'incident_type' => ['required','string','max:80'],
            'severity' => ['required','in:minor,major,critical'],
            'location' => ['nullable','string','max:180'],
            'description' => ['nullable','string'],
            'estimated_cost' => ['nullable','numeric','min:0'],
            'settled_cost' => ['nullable','numeric','min:0'],
            'status' => ['required','in:Open,Under Review,Settled,Closed'],
        ];
    }
}
