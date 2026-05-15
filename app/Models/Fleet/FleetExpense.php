<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetExpense extends Model
{
    protected $guarded = [];
    protected $casts = ['expense_date'=>'date'];
    public function vehicle(){ return $this->belongsTo(FleetVehicle::class); }
    public function driver(){ return $this->belongsTo(FleetDriver::class); }
}
