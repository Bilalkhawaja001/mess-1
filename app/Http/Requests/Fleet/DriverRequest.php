<?php

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

class DriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:150',
            'employee_code' => 'nullable|max:50',
            'cnic' => 'nullable|max:30',
            'mobile_no' => 'nullable|max:30',
            'license_no' => 'nullable|max:100',
            'license_expiry_date' => 'nullable|date',
            'status' => 'required|in:Active,Inactive'
        ];
    }
}
