<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenIssueTarget extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_COMPLETED = 'completed';

    protected $guarded = [];

    protected $casts = [
        'target_date' => 'date',
        'required_qty' => 'decimal:3',
        'issued_qty' => 'decimal:3',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }

    public function pendingQty(): float
    {
        return round(max((float) $this->required_qty - (float) $this->issued_qty, 0), 3);
    }
}
