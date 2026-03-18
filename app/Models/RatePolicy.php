<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatePolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'rate_type','value','effective_from','effective_to','is_active','approved_by_user_id','approved_at'
    ];

    protected $casts = [
        'value' => 'decimal:4',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
    ];
}
