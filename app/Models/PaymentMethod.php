<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'is_manual',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'is_manual' => 'boolean',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
