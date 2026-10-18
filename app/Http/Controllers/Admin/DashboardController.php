<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\ListingReport;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $mrr = Subscription::whereIn('subscriptions.status', ['active', 'past_due'])
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->where('plans.billing_period', 'monthly')->sum('plans.price');

        return view('admin.dashboard', [
            'stats' => [
                'Pengguna' => User::count(),
                'Tenant aktif' => Tenant::where('status', 'active')->count(),
                'Tenant ditangguhkan' => Tenant::where('status', 'suspended')->count(),
                'Iklan tayang' => Listing::where('status', 'published')->count(),
                'Menunggu moderasi' => Listing::where('status', 'pending_review')->count(),
                'Laporan terbuka' => ListingReport::where('status', 'open')->count(),
                'Lead 30 hari' => Lead::where('created_at', '>=', now()->subDays(30))->count(),
                'Invoice tertunda' => Invoice::where('status', 'pending')->count(),
            ],
            'mrr' => $mrr,
            'recentAudit' => AuditLog::with(['actor', 'tenant'])->latest('id')->take(10)->get(),
        ]);
    }

    public function audit(Request $request)
    {
        $action = trim((string) $request->query('aksi'));
        $logs = AuditLog::with(['actor', 'tenant'])
            ->when($action, fn ($q) => $q->where('action', 'like', $action.'%'))
            ->latest('id')->paginate(40)->withQueryString();

        return view('admin.audit', compact('logs', 'action'));
    }
}
