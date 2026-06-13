<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    // Legacy compatibility
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_APPROVED = 'APPROVED';

    // Payment architecture lifecycle
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_INITIATED = 'INITIATED';
    public const STATUS_SUCCESS = 'SUCCESS';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_REFUNDED = 'REFUNDED';
    public const STATUS_REVERSED = 'REVERSED';
    public const STATUS_RECONCILIATION_PENDING = 'RECONCILIATION_PENDING';
    public const STATUS_RECONCILED = 'RECONCILED';

    public const DUPLICATE_GUARD_VERSION = 'phase2c_app_v1';

    protected $fillable = [
        'member_id',
        'bill_id',
        'month_cycle',
        'duplicate_guard_version',
        'active_month_guard_key',
        'active_month_guard_key_v2',
        'payment_method_id',
        'payment_ref',
        'payment_date',
        'amount',
        'currency',
        'method',
        'reference_no',
        'notes',
        'status',
        'posted_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'edited_by_user_id',
        'edited_at',
        'edit_reason',
        'refunded_amount',
        'reversed_amount',
        'last_attempt_id',
        'last_transaction_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'approved_at' => 'datetime',
        'edited_at' => 'datetime',
        'amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'reversed_amount' => 'decimal:2',
    ];

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

    public function attempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(PaymentReconciliation::class);
    }
}
