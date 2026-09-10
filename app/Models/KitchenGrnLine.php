<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitchenGrnLine extends Model
{
    protected $table = 'kitchen_grn_lines';

    protected $fillable = ['kitchen_grn_id', 'purchase_order_line_id', 'item_id', 'qty_received', 'unit_cost'];

    public function goodsReceipt()
    {
        return $this->belongsTo(KitchenGrn::class, 'kitchen_grn_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function purchaseOrderLine()
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'purchase_order_line_id');
    }
}
