<?php

namespace App\Http\Requests\Members;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:members,member_code'],
            'name' => ['required', 'string', 'max:120'],
            'department_name' => ['nullable', 'string', 'max:120'],
            'mess_id' => ['nullable', 'exists:messes,id'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'join_date' => ['required', 'date'],
            'leave_date' => ['nullable', 'date', 'after_or_equal:join_date'],
            'is_active' => ['nullable', 'boolean'],
            'user_id' => ['nullable', 'exists:users,id', 'unique:members,user_id'],
        ];
    }
}
