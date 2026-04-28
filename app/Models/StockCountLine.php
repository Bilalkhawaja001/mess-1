<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCountLine extends Model
{
    protected $fillable = [
        'stock_count_id',
        'item_id',
        'system_qty',
        'counted_qty',
        'variance_qty',
        'remarks',
    ];

    protected $casts = [
        'system_qty' => 'decimal:3',
        'counted_qty' => 'decimal:3',
        'variance_qty' => 'decimal:3',
    ];

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
