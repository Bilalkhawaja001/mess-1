<?php
namespace App\Http\Requests\Rates;
use Illuminate\Foundation\Http\FormRequest;
class StoreRatePolicyRequest extends FormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array {
  return [
   'rate_type'=>['required','string','max:50'],
   'value'=>['required','numeric','min:0'],
   'effective_from'=>['required','date'],
   'effective_to'=>['nullable','date','after:effective_from'],
   'is_active'=>['nullable','boolean'],
  ];
 }
}
