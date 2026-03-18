<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = (int) $this->route('user');

        return [
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($userId)],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:120', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
