<?php

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

class ChallanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:fleet_vehicles,id'],
            'driver_id' => ['nullable', 'integer', 'exists:fleet_drivers,id'],
            'challan_no' => ['nullable', 'string', 'max:120'],
            'violation_type' => ['nullable', 'string', 'max:120'],
            'challan_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:Unpaid,Paid,Disputed,Cancelled'],
        ];
    }
}
