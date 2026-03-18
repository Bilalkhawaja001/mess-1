<?php
namespace App\Http\Requests\Extras;
use Illuminate\Foundation\Http\FormRequest;
class StoreExtraRequest extends FormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array {
  return [
   'extra_date'=>['required','date'],
   'member_id'=>['required','exists:members,id'],
   'description'=>['required','string','max:255'],
   'amount'=>['required','numeric','min:0.01'],
  ];
 }
}
