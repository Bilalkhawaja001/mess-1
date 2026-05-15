<?php
namespace App\Models\Fleet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FleetTyreBattery extends Model
{
    use SoftDeletes;

    protected $table = 'fleet_tyres_batteries';
    protected $guarded = [];
    protected $casts = ['installed_at'=>'date','removed_at'=>'date','warranty_expiry'=>'date','cost'=>'decimal:2'];
    public function vehicle(){ return $this->belongsTo(FleetVehicle::class, 'vehicle_id'); }
    public function driver(){ return $this->belongsTo(FleetDriver::class, 'driver_id'); }
}
