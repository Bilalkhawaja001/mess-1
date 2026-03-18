<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberRegistrationOtp extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'mobile_number',
        'otp_hash',
        'expires_at',
        'attempts',
        'resend_count',
        'last_sent_at',
        'verified_at',
        'status',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
