<?php
namespace App\Http\Requests\Settings;
use Illuminate\Foundation\Http\FormRequest;

class StoreSettingRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'setting_key'=>['required','string','max:120'],
            'setting_value'=>['nullable','string'],
            'value_type'=>['required','in:string,int,float,bool,json'],
            'is_active'=>['nullable','boolean'],
        ];
    }
}
