<?php

namespace App\Http\Middleware;

use App\Models\TenantMembership;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Urutan request tenant (Blueprint §19): identifikasi tenant → validasi status →
 * validasi keanggotaan & role → (policy di controller) → limit paket di service.
 */
class ResolveTenant
{
    public function __construct(protected TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $memberships = $user->memberships()->where('status', 'active')->with('tenant')->get();

        if ($memberships->isEmpty()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                abort(403, 'Akun ini belum terdaftar pada dealer mana pun.');
            }

            return redirect()->route('dealer.onboarding');
        }

        // Tenant dipilih dari sesi pengguna; tidak ada fallback diam-diam ke tenant yang bukan miliknya.
        $membership = $memberships->firstWhere('tenant_id', $user->current_tenant_id) ?? $memberships->first();
        if ((int) $user->current_tenant_id !== (int) $membership->tenant_id) {
            $user->forceFill(['current_tenant_id' => $membership->tenant_id])->save();
        }

        $tenant = $membership->tenant;
        if ($tenant->status === 'closed') {
            abort(403, 'Dealer ini sudah ditutup.');
        }

        // Tenant suspended: hanya boleh melihat dashboard, langganan/invoice, dan berpindah dealer.
        if ($tenant->status === 'suspended' && $request->is('api/*')) {
            abort(403, 'Dealer sedang ditangguhkan.');
        }
        if ($tenant->status === 'suspended' && ! $request->routeIs('dealer.dashboard', 'dealer.billing', 'dealer.switch', 'dealer.suspended')) {
            return redirect()->route('dealer.suspended');
        }

        $this->context->set($tenant, $membership);
        view()->share('currentTenant', $tenant);
        view()->share('currentMembership', $membership);
        view()->share('userMemberships', $memberships);

        return $next($request);
    }
}
