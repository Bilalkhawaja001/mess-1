<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'member_id',
        'bill_id',
        'payment_method_id',
        'attempt_ref',
        'amount',
        'currency',
        'status',
        'audit_payload',
        'expires_at',
        'created_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'audit_payload' => 'array',
        'expires_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Billing::class, 'bill_id');
    }

    public function methodRecord(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}
