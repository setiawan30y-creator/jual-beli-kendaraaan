<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\TenantMembership;

/**
 * Tenant aktif untuk request saat ini. Diisi oleh middleware ResolveTenant
 * setelah keanggotaan divalidasi — tenant_id dari client tidak pernah dipercaya.
 */
class TenantContext
{
    protected ?Tenant $tenant = null;

    protected ?TenantMembership $membership = null;

    public function set(Tenant $tenant, TenantMembership $membership): void
    {
        $this->tenant = $tenant;
        $this->membership = $membership;
    }

    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function membership(): ?TenantMembership
    {
        return $this->membership;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function can(string $permission): bool
    {
        return (bool) $this->membership?->can($permission);
    }

    public function authorize(string $permission): void
    {
        abort_unless($this->can($permission), 403, 'Anda tidak memiliki izin untuk tindakan ini.');
    }

    /** Pastikan resource milik tenant aktif; 404 agar ID tenant lain tidak bisa ditebak. */
    public function ensureOwns(?object $model): void
    {
        abort_if(! $model || ! $this->tenant || (int) ($model->tenant_id ?? 0) !== (int) $this->tenant->id, 404);
    }
}
