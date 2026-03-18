<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'member_code',
        'name',
        'department_name',
        'mobile_number',
        'join_date',
        'leave_date',
        'is_active',
        'portal_enabled',
        'mobile_verified_at',
        'registered_at',
    ];

    protected $casts = [
        'join_date' => 'date',
        'leave_date' => 'date',
        'is_active' => 'boolean',
        'portal_enabled' => 'boolean',
        'mobile_verified_at' => 'datetime',
        'registered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function billings(): HasMany
    {
        return $this->hasMany(Billing::class);
    }

    public function bills(): HasMany
    {
        return $this->billings();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(MemberLedger::class);
    }
}
