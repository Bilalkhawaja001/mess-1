<?php
namespace App\Http\Requests\Attendance;
use Illuminate\Foundation\Http\FormRequest;

class MonthlyAttendanceRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'month_cycle'=>['required','regex:/^\d{4}-\d{2}$/'],
            'rows'=>['required','array'],
            'rows.*.member_id'=>['required','exists:members,id'],
            'rows.*.present_days'=>['required','integer','min:0','max:31'],
        ];
    }
}
