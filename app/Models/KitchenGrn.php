<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitchenGrn extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    protected $table = 'kitchen_grns';

    protected $fillable = [
        'temp_number', 'kitchen_staff_id', 'purchase_order_id', 'received_date', 'remarks',
        'status', 'reject_reason', 'reviewed_by_user_id', 'reviewed_at', 'goods_receipt_id',
    ];

    protected $casts = [
        'received_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(KitchenGrnLine::class, 'kitchen_grn_id');
    }

    public function staff()
    {
        return $this->belongsTo(KitchenStaff::class, 'kitchen_staff_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function goodsReceipt()
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }
}
