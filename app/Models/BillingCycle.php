<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'month_cycle',
        'status',
        'is_closed',
        'closed_at',
        'closed_by_user_id',
        'close_reason',
    ];

    protected $casts = [
        'is_closed' => 'boolean',
        'closed_at' => 'datetime',
    ];
}
