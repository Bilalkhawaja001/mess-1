<?php

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

class VehicleDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => 'required|exists:fleet_vehicles,id',
            'document_type' => 'required|max:100',
            'document_no' => 'nullable|max:100',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'required|date'
        ];
    }
}
