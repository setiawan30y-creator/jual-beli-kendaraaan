<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\PlanLimits;
use App\Support\TenantContext;

class BillingController extends Controller
{
    public function index(TenantContext $ctx, PlanLimits $limits)
    {
        $tenant = $ctx->tenant();

        return view('dealer.billing', [
            'subscription' => $tenant->activeSubscription?->load('plan'),
            'plans' => Plan::where('status', 'active')->orderBy('sort_order')->get(),
            'usage' => $limits->summary($tenant),
            'invoices' => $ctx->can('billing.view') ? $tenant->invoices()->latest()->take(24)->get() : collect(),
        ]);
    }
}
