<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetTripLog extends Model
{
    protected $guarded = [];
    protected $casts = ['trip_date'=>'date'];
    public function vehicle(){ return $this->belongsTo(FleetVehicle::class); }
    public function driver(){ return $this->belongsTo(FleetDriver::class); }
}
