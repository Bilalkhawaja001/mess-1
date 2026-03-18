<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_date',
        'member_id',
        'present',
        'breakfast',
        'lunch',
        'dinner',
        'marked_by_user_id',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'present' => 'boolean',
        'breakfast' => 'boolean',
        'lunch' => 'boolean',
        'dinner' => 'boolean',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by_user_id');
    }
}
