<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    public const SEVERITY_NORMAL = 'normal';
    public const SEVERITY_MODERATE = 'moderate';
    public const SEVERITY_STRICT = 'strict';
    public const SEVERITY_FINAL = 'final';

    protected $fillable = [
        'title',
        'message',
        'target_type',
        'severity',
        'target_member_ids',
        'sent_by_user_id',
        'sent_at',
        'total_tokens',
        'success_count',
        'failed_count',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'target_member_ids' => 'array',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
