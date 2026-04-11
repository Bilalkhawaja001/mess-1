<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MealPlan extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';

    protected $guarded = [];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
