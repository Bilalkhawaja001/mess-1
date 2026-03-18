<?php

namespace App\Http\Requests\Ledger;

use Illuminate\Foundation\Http\FormRequest;

class StoreLedgerAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'exists:members,id'],
            'entry_date' => ['required', 'date'],
            'debit' => ['nullable', 'numeric', 'min:0'],
            'credit' => ['nullable', 'numeric', 'min:0'],
            'reason_code' => ['nullable', 'string', 'max:80'],
        ];
    }
}
