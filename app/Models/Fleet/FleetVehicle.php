<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetVehicle extends Model
{
    protected $guarded = [];
    public function driver(){ return $this->belongsTo(FleetDriver::class, 'assigned_driver_id'); }
    public function fuelLogs(){ return $this->hasMany(FleetFuelLog::class, 'vehicle_id'); }
    public function maintenanceLogs(){ return $this->hasMany(FleetMaintenanceLog::class, 'vehicle_id'); }
    public function documents(){ return $this->hasMany(FleetVehicleDocument::class, 'vehicle_id'); }
    public function trips(){ return $this->hasMany(FleetTripLog::class, 'vehicle_id'); }
}
