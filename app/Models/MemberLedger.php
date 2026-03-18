<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'entry_date',
        'debit',
        'credit',
        'ref_type',
        'ref_id',
        'balance_after',
        'reason_code',
        'is_opening_balance',
        'posted_by_user_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
