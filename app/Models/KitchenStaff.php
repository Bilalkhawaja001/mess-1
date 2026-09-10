<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitchenStaff extends Model
{
    protected $table = 'kitchen_staff';

    protected $fillable = [
        'staff_code', 'name', 'username', 'password', 'mobile_number',
        'is_active', 'can_view_outstanding', 'api_token_hash',
        'last_login_at', 'created_by_user_id',
    ];

    protected $hidden = ['password', 'api_token_hash'];

    protected $casts = [
        'is_active' => 'boolean',
        'can_view_outstanding' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function purchaseOrders()
    {
        return $this->hasMany(KitchenPo::class, 'kitchen_staff_id');
    }

    public function goodsReceipts()
    {
        return $this->hasMany(KitchenGrn::class, 'kitchen_staff_id');
    }
}
