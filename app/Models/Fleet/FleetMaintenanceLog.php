<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetMaintenanceLog extends Model
{
    protected $guarded = [];
    protected $casts = ['maintenance_date'=>'date','next_service_date'=>'date','is_overdue'=>'boolean'];
    public function vehicle(){ return $this->belongsTo(FleetVehicle::class); }
}
