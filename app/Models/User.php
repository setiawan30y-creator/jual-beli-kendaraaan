<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const PLATFORM_ROLES = ['user', 'moderator', 'support', 'platform_admin', 'super_admin'];

    protected $fillable = ['name', 'email', 'phone', 'password', 'theme_mode'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_memberships')
            ->withPivot(['role', 'status', 'branch_id'])
            ->wherePivot('status', 'active');
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class, 'listing_favorites')->withPivot('created_at');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'seller_user_id');
    }

    public function isPlatformStaff(): bool
    {
        return in_array($this->platform_role, ['moderator', 'support', 'platform_admin', 'super_admin'], true);
    }

    public function isPlatformAdmin(): bool
    {
        return in_array($this->platform_role, ['platform_admin', 'super_admin'], true);
    }

    public function canModerate(): bool
    {
        return in_array($this->platform_role, ['moderator', 'platform_admin', 'super_admin'], true);
    }

    public function activeMembershipFor(int $tenantId): ?TenantMembership
    {
        return $this->memberships()->where('tenant_id', $tenantId)->where('status', 'active')->first();
    }
}
