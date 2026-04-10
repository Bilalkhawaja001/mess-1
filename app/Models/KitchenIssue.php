<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitchenIssue extends Model
{
    protected $guarded = [];

    public function mess()
    {
        return $this->belongsTo(Mess::class);
    }
}
