<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestMeal extends Model
{
    protected $guarded = [];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
