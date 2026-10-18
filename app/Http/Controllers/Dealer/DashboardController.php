<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Lead;
use App\Services\PlanLimits;
use App\Services\WeatherService;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(TenantContext $ctx, PlanLimits $limits, WeatherService $weather)
    {
        $tenant = $ctx->tenant();
        $membership = $ctx->membership();
        $branch = $membership->branch ?? $tenant->branches()->where('is_default', true)->first() ?? $tenant->branches()->first();
        $branch?->loadMissing('city');

        $units = $tenant->units()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $leadQuery = $tenant->leads();
        if (! $ctx->can('leads.view_all')) {
            $leadQuery->where('assigned_user_id', $membership->user_id);
        }

        return view('dealer.dashboard', [
            'units' => $units,
            'activeListings' => $tenant->listings()->where('status', 'published')->count(),
            'pendingListings' => $tenant->listings()->where('status', 'pending_review')->count(),
            'newLeads' => (clone $leadQuery)->where('status', 'baru')->count(),
            'followUps' => (clone $leadQuery)->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now())->whereNotIn('status', ['menang', 'tidak_lanjut'])->count(),
            'bookings' => $tenant->bookings()->where('status', 'menunggu')->count(),
            'soldThisMonth' => $tenant->listings()->where('status', 'sold')->where('updated_at', '>=', now()->startOfMonth())->count(),
            'recentLeads' => (clone $leadQuery)->with('listing')->latest()->take(6)->get(),
            'upcomingBookings' => $tenant->bookings()->with('listing', 'buyer')->where('starts_at', '>=', now())->orderBy('starts_at')->take(5)->get(),
            'usage' => $limits->summary($tenant),
            'branch' => $branch,
            'weather' => $branch ? $weather->current($branch->latitude, $branch->longitude) : null,
            'onboarding' => [
                ['label' => 'Profil dealer', 'done' => (bool) $tenant->description],
                ['label' => 'Cabang & lokasi', 'done' => $tenant->branches()->exists()],
                ['label' => 'Undang staf', 'done' => $tenant->memberships()->count() > 1],
                ['label' => 'Unit pertama', 'done' => $tenant->units()->exists()],
                ['label' => 'Iklan pertama diajukan', 'done' => $tenant->listings()->where('status', '!=', 'draft')->exists()],
            ],
        ]);
    }

    public function suspended(TenantContext $ctx)
    {
        return view('dealer.suspended', ['tenant' => $ctx->tenant()]);
    }

    public function switchTenant(Request $request, int $tenantId)
    {
        $membership = $request->user()->activeMembershipFor($tenantId);
        abort_unless($membership, 404);
        $request->user()->forceFill(['current_tenant_id' => $tenantId])->save();

        return redirect()->route('dealer.dashboard')->with('status', 'Beralih ke '.$membership->tenant->name.'.');
    }
}
