<?php

namespace App\Http\Requests\Members;

use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $routeMember = $this->route('member');
        $memberId = $routeMember instanceof Member ? $routeMember->id : (int) $routeMember;

        return [
            'member_code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('members', 'member_code')->ignore($memberId)],
            'name' => ['required', 'string', 'max:120'],
            'department_name' => ['nullable', 'string', 'max:120'],
            'mess_id' => ['nullable', 'exists:messes,id'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'join_date' => ['required', 'date'],
            'leave_date' => ['nullable', 'date', 'after_or_equal:join_date'],
            'is_active' => ['nullable', 'boolean'],
            'user_id' => ['nullable', 'exists:users,id', Rule::unique('members', 'user_id')->ignore($memberId)],
        ];
    }
}
