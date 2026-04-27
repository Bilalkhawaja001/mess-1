<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'name',
        'member_id',
        'email',
        'password',
        'role_id',
        'is_active',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'must_change_password' => 'boolean',
        'password' => 'hashed',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    public function linkedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    public function resolvedMemberProfile(): ?Member
    {
        return $this->linkedMember ?: $this->member;
    }

    public function hasLinkedMemberProfile(): bool
    {
        return $this->resolvedMemberProfile() !== null;
    }

    public function isAdminLike(): bool
    {
        return in_array(optional($this->role)->code, ['SUPER_ADMIN', 'ADMIN'], true);
    }

    public function isMemberRole(): bool
    {
        return optional($this->role)->code === 'MEMBER';
    }

    public function hasPermission(string $permissionCode): bool
    {
        $role = $this->role;
        if (! $role) {
            return false;
        }

        if ($role->code === 'SUPER_ADMIN') {
            return true;
        }

        return $role->permissions()->where('code', $permissionCode)->exists();
    }
}
