<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'member_code',
        'name',
        'department_name',
        'mess_id',
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

    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
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

    public function extras(): HasMany
    {
        return $this->hasMany(Extra::class);
    }

    public function monthlyAttendances(): HasMany
    {
        return $this->hasMany(MonthlyAttendance::class);
    }

    public function registrationOtps(): HasMany
    {
        return $this->hasMany(MemberRegistrationOtp::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'user_id', 'user_id');
    }

    public function removalDependencies(): Collection
    {
        return collect([
            'attendance' => $this->attendances()->exists(),
            'monthly_attendance' => $this->monthlyAttendances()->exists(),
            'billing' => $this->billings()->exists(),
            'payments' => $this->payments()->exists(),
            'payment_attempts' => $this->paymentAttempts()->exists(),
            'payment_transactions' => $this->paymentTransactions()->exists(),
            'ledger' => $this->ledgers()->exists(),
            'extras' => $this->extras()->exists(),
            'registration_otp' => $this->registrationOtps()->exists(),
            'linked_user' => $this->user_id !== null || User::query()->where('member_id', $this->id)->exists(),
        ])->filter();
    }

    public function canBePermanentlyDeleted(): bool
    {
        return $this->removalDependencies()->isEmpty();
    }
}
