<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;
    protected $fillable = ['setting_key','setting_value','value_type','is_active','updated_by_user_id'];
    protected $casts = ['is_active'=>'boolean'];
}
