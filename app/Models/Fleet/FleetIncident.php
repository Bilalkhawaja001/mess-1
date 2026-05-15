<?php
namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FleetIncident extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $casts = [
        'incident_date' => 'date',
        'estimated_cost' => 'decimal:2',
        'settled_cost' => 'decimal:2',
    ];

    public function vehicle(){ return $this->belongsTo(FleetVehicle::class, 'vehicle_id'); }
    public function driver(){ return $this->belongsTo(FleetDriver::class, 'driver_id'); }
}
