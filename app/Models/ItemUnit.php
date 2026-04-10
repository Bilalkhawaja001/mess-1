<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemUnit extends Model
{
    protected $fillable = [
        'item_id',
        'unit_code',
        'factor_to_base',
        'is_default_for_grn',
        'is_default_for_kitchen',
    ];

    protected $casts = [
        'factor_to_base' => 'decimal:4',
        'is_default_for_grn' => 'bool',
        'is_default_for_kitchen' => 'bool',
    ];
}
