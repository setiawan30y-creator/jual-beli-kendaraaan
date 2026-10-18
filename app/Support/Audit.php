<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class Audit
{
    public static function log(string $action, ?Model $resource = null, ?array $before = null, ?array $after = null, ?string $reason = null, ?int $tenantId = null): AuditLog
    {
        $request = request();

        return AuditLog::create([
            'tenant_id' => $tenantId ?? ($resource?->tenant_id ?? app(TenantContext::class)->id()),
            'actor_user_id' => auth()->id(),
            'action' => $action,
            'resource_type' => $resource ? class_basename($resource) : null,
            'resource_id' => $resource?->getKey(),
            'reason' => $reason,
            'before_json' => $before,
            'after_json' => $after,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 250) : null,
        ]);
    }
}
