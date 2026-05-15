<?php
namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FleetSetting extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'value' => 'string',
    ];
}
