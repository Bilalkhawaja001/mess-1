<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessCosting extends Model
{
    protected $fillable = [
        'month_cycle',
        'mess_id',
        'food_cost',
        'gas_cost',
        'include_gas_cost',
        'salary_cost',
        'include_salary_cost',
        'other_cost',
        'total_cost',
        'member_count',
        'active_days_total',
        'cost_per_member',
        'cost_per_day',
        'comparison_json',
        'snapshot_json',
        'created_by',
    ];

    protected $casts = [
        'food_cost' => 'decimal:2',
        'gas_cost' => 'decimal:2',
        'include_gas_cost' => 'boolean',
        'salary_cost' => 'decimal:2',
        'include_salary_cost' => 'boolean',
        'other_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'member_count' => 'integer',
        'active_days_total' => 'decimal:2',
        'cost_per_member' => 'decimal:2',
        'cost_per_day' => 'decimal:4',
        'comparison_json' => 'array',
        'snapshot_json' => 'array',
    ];

    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
