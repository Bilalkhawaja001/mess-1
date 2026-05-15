<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetVehicleDocument extends Model
{
    protected $guarded = [];
    protected $casts = ['issue_date'=>'date','expiry_date'=>'date'];
    public function vehicle(){ return $this->belongsTo(FleetVehicle::class); }
}
