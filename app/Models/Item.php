<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'category',
        'uom',
        'reorder_level',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function units()
    {
        return $this->hasMany(ItemUnit::class);
    }

    protected static function booted(): void
    {
        static::created(function (Item $item) {
            if (! $item->uom) {
                return;
            }

            $item->units()->firstOrCreate(
                ['unit_code' => $item->uom],
                [
                    'factor_to_base' => 1.0,
                    'is_default_for_grn' => true,
                    'is_default_for_kitchen' => true,
                ]
            );
        });
    }
}
