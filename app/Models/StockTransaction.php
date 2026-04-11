<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    public const TXN_TYPE_KITCHEN_ISSUE = 'KITCHEN_ISSUE';

    protected $fillable = [
        'item_id',
        'txn_type',
        'quantity',
        'unit_cost',
        'trans_unit_code',
        'trans_quantity',
        'reference_type',
        'reference_id',
        'remarks',
        'txn_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'trans_quantity' => 'decimal:3',
        'txn_at' => 'datetime',
    ];
}
