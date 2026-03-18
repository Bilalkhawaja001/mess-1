<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthClosure extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'OPEN';
    public const STATUS_CLOSED = 'CLOSED';
    public const STATUS_HARD_RESET = 'HARD_RESET';

    protected $fillable = [
        'month_cycle',
        'status',
        'closed_by_user_id',
        'closed_at',
        'reopened_by_user_id',
        'reopened_at',
        'hard_reset_by_user_id',
        'hard_reset_at',
        'reason',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
        'hard_reset_at' => 'datetime',
    ];
}
