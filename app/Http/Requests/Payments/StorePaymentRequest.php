<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'exists:members,id'],
            'bill_id' => ['required', 'exists:billings,id'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'payment_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['nullable', 'string'],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'idempotency_key' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
