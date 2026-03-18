<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CreateMemberAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'exists:members,id'],
            'email' => ['required', 'email:rfc,dns', 'max:120', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->letters()->numbers()->symbols()],
            'force_password_change' => ['nullable', 'boolean'],
            'mark_mobile_verified' => ['nullable', 'boolean'],
        ];
    }
}
