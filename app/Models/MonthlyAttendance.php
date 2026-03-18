<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyAttendance extends Model
{
    use HasFactory;
    protected $fillable = ['month_cycle','member_id','present_days','approved_by_user_id','approved_at','is_locked'];
    protected $casts = ['approved_at'=>'datetime','is_locked'=>'boolean'];

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
}
