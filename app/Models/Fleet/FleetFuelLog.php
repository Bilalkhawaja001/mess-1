<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetFuelLog extends Model
{
    protected $guarded = [];
    protected $casts = ['fuel_date'=>'date','is_abnormal_average'=>'boolean'];
    public function vehicle(){ return $this->belongsTo(FleetVehicle::class); }
    public function driver(){ return $this->belongsTo(FleetDriver::class); }
}
