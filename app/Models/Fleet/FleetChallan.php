<?php
namespace App\Models\Fleet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FleetChallan extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    protected $casts = ['challan_date'=>'date','due_date'=>'date','paid_at'=>'datetime','amount'=>'decimal:2'];
    public function vehicle(){ return $this->belongsTo(FleetVehicle::class, 'vehicle_id'); }
    public function driver(){ return $this->belongsTo(FleetDriver::class, 'driver_id'); }
}
