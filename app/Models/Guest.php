<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function hostMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'host_member_id');
    }

    public function meals(): HasMany
    {
        return $this->hasMany(GuestMeal::class);
    }
}
