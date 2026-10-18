<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantMembership extends Model
{
    public const ROLES = [
        'tenant_owner' => 'Pemilik',
        'tenant_admin' => 'Admin Dealer',
        'branch_manager' => 'Manajer Cabang',
        'sales' => 'Sales',
        'inventory_staff' => 'Staf Inventaris',
        'finance_staff' => 'Staf Keuangan',
        'inspector' => 'Inspektor',
        'read_only' => 'Hanya Lihat',
    ];

    protected $guarded = ['id'];

    protected $casts = ['joined_at' => 'datetime'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(TenantBranch::class, 'branch_id');
    }

    public function can(string $permission): bool
    {
        $allowed = config('marketplace.tenant_permissions.'.$this->role, []);

        return in_array('*', $allowed, true) || in_array($permission, $allowed, true);
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }
}
