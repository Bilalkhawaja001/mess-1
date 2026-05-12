<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Billing extends Model
{
    use HasFactory;

    protected $fillable = [
        'month_cycle',
        'member_id',
        'active_days',
        'rate_per_day',
        'base_amount',
        'extras_amount',
        'net_payable',
        'due_date',
        'is_locked',
        'generated_by_user_id',
        'reversed_from_billing_id',
        'billing_status',
        'correction_reason',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'rate_per_day' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'extras_amount' => 'decimal:2',
        'net_payable' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class, 'bill_id');
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'bill_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'bill_id');
    }
}
