<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MemberLifecycleActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'force_password_change' => ['nullable', 'boolean'],
            'mark_mobile_verified' => ['nullable', 'boolean'],
        ];
    }
}
