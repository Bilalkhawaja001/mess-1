<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'payment_transaction_id',
        'member_id',
        'bill_id',
        'status',
        'ledger_sync_status',
        'accounting_sync_status',
        'mismatch_reason',
        'notes',
        'reconciled_by_user_id',
        'reconciled_at',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'reconciled_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
