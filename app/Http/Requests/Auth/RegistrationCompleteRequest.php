<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegistrationCompleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc,dns', 'max:120', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->letters()->numbers()->symbols()],
        ];
    }
}
