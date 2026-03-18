<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendance_date' => ['required', 'date'],
            'attendance' => ['array'],
            'attendance.*.member_id' => ['required', 'exists:members,id'],
            'attendance.*.breakfast' => ['nullable', 'boolean'],
            'attendance.*.lunch' => ['nullable', 'boolean'],
            'attendance.*.dinner' => ['nullable', 'boolean'],
            'present_all' => ['nullable', 'boolean'],
        ];
    }
}
