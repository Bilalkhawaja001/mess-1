<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingRun extends Model
{
    use HasFactory;

    protected $fillable = ['month_cycle','scope_hash','config_hash','status','inserted_count','skipped_count','created_by_user_id'];
}
