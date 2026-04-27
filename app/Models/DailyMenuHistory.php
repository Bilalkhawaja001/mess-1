<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyMenuHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_menu_id',
        'action',
        'changed_by',
        'snapshot',
    ];

    protected $casts = [
        'snapshot' => 'array',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'daily_menu_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
