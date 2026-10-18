<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Services\PlanLimits;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->query('status'), Tenant::STATUSES, true) ? $request->query('status') : null;
        $tenants = Tenant::with(['owner', 'activeSubscription.plan', 'city'])
            ->withCount(['listings as active_listings_count' => fn ($q) => $q->where('status', 'published'), 'memberships'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.tenants.index', compact('tenants', 'status'));
    }

    public function show(int $tenantId, PlanLimits $limits)
    {
        $tenant = Tenant::with(['owner', 'memberships.user', 'branches.city', 'activeSubscription.plan', 'invoices'])->findOrFail($tenantId);

        return view('admin.tenants.show', [
            'tenant' => $tenant,
            'usage' => $limits->summary($tenant),
            'logs' => AuditLog::where('tenant_id', $tenant->id)->with('actor')->latest('id')->take(20)->get(),
        ]);
    }

    /** Suspend/aktifkan dengan alasan wajib & audit; data tidak dihapus (Blueprint §17.8). */
    public function status(Request $request, int $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended', 'closed'])],
            'reason' => ['required', 'string', 'max:255'],
        ], [], ['reason' => 'alasan']);
        $before = $tenant->status;
        $tenant->status = $data['status'];
        $tenant->status_reason = $data['reason'];
        $tenant->save();
        Audit::log('platform.tenant_status_changed', $tenant, ['status' => $before], ['status' => $tenant->status], $data['reason'], $tenant->id);

        return back()->with('status', 'Status tenant diperbarui.');
    }

    public function verify(Request $request, int $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $data = $request->validate([
            'verification_status' => ['required', Rule::in(['verified', 'rejected', 'pending', 'unverified'])],
            'reason' => ['required', 'string', 'max:255'],
        ]);
        $before = $tenant->verification_status;
        $tenant->verification_status = $data['verification_status'];
        $tenant->save();
        Audit::log('platform.tenant_verification', $tenant, ['verification_status' => $before], ['verification_status' => $tenant->verification_status], $data['reason'], $tenant->id);

        return back()->with('status', 'Status verifikasi dealer diperbarui.');
    }
}
