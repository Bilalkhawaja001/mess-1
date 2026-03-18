<?php
namespace App\Http\Requests\Summary;
use Illuminate\Foundation\Http\FormRequest;
class SummaryFilterRequest extends FormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array {
  return [
   'month_cycle'=>['nullable','regex:/^\d{4}-\d{2}$/'],
   'export'=>['nullable','in:csv,xlsx'],
  ];
 }
}
