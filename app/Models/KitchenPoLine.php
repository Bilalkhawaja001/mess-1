<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitchenPoLine extends Model
{
    protected $table = 'kitchen_po_lines';

    protected $fillable = ['kitchen_po_id', 'item_id', 'qty_ordered', 'unit_price'];

    public function purchaseOrder()
    {
        return $this->belongsTo(KitchenPo::class, 'kitchen_po_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
