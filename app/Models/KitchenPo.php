<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitchenPo extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    protected $table = 'kitchen_pos';

    protected $fillable = [
        'temp_number', 'kitchen_staff_id', 'vendor_id', 'po_date', 'remarks',
        'status', 'reject_reason', 'reviewed_by_user_id', 'reviewed_at', 'purchase_order_id',
    ];

    protected $casts = [
        'po_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(KitchenPoLine::class, 'kitchen_po_id');
    }

    public function staff()
    {
        return $this->belongsTo(KitchenStaff::class, 'kitchen_staff_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}
