<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Item extends Model { protected $fillable=['name','sku','uom','reorder_level','is_active']; protected $casts=['is_active'=>'bool']; }
