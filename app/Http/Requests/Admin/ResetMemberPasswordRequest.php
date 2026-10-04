<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ResetMemberPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdminLike();
    }

    public function rules(): array
    {
        return [
            'new_password' => ['required', 'string', 'min:6'],
            'confirm_password' => ['required', 'same:new_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_password.min' => 'Password must be at least 6 characters.',
        ];
    }
}
