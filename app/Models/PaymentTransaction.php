<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'payment_attempt_id',
        'member_id',
        'bill_id',
        'payment_method_id',
        'internal_ref',
        'external_ref',
        'merchant_ref',
        'idempotency_key',
        'amount',
        'currency',
        'status',
        'failure_reason',
        'raw_request_summary',
        'raw_response_summary',
        'initiated_at',
        'completed_at',
        'verified_at',
        'created_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'raw_request_summary' => 'array',
        'raw_response_summary' => 'array',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
