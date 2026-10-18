<?php

namespace App\Services;

use App\Models\Tenant;

/** Batas paket diperiksa di backend pada setiap aksi yang memakai kuota (Blueprint §17.2). */
class PlanLimits
{
    public function usage(Tenant $tenant): array
    {
        return [
            'listing_limit' => $tenant->listings()->whereIn('status', ['pending_review', 'published', 'reserved'])->count(),
            'staff_limit' => $tenant->memberships()->whereIn('status', ['active', 'invited'])->count(),
            'branch_limit' => $tenant->branches()->where('status', 'active')->count(),
        ];
    }

    public function limit(Tenant $tenant, string $key): ?int
    {
        $plan = $tenant->activeSubscription?->plan;

        return $plan ? $plan->limit($key) : 0;
    }

    public function canConsume(Tenant $tenant, string $key, int $amount = 1): bool
    {
        $limit = $this->limit($tenant, $key);
        if ($limit === null) {
            return true;
        }

        return ($this->usage($tenant)[$key] ?? 0) + $amount <= $limit;
    }

    public function summary(Tenant $tenant): array
    {
        $usage = $this->usage($tenant);
        $out = [];
        foreach (['listing_limit' => 'Iklan aktif', 'staff_limit' => 'Anggota staf', 'branch_limit' => 'Cabang'] as $key => $label) {
            $out[$key] = ['label' => $label, 'used' => $usage[$key], 'limit' => $this->limit($tenant, $key)];
        }

        return $out;
    }
}
