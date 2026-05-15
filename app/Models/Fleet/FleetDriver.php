<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetDriver extends Model
{
    protected $guarded = [];
    public function vehicle(){ return $this->hasOne(FleetVehicle::class, 'assigned_driver_id'); }
    public function fuelLogs(){ return $this->hasMany(FleetFuelLog::class, 'driver_id'); }
    public function trips(){ return $this->hasMany(FleetTripLog::class, 'driver_id'); }
}
