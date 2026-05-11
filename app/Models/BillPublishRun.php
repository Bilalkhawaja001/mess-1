<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillPublishRun extends Model
{
    protected $fillable = [
        'month_cycle',
        'published_by_user_id',
        'published_at',
        'bill_count',
        'total_bill_amount',
        'total_tokens',
        'success_count',
        'failed_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'total_bill_amount' => 'decimal:2',
    ];

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }
}
