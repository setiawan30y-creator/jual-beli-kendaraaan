<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Support\Audit;
use Illuminate\Http\Request;

/** Paket & limit dapat diubah dari admin tanpa deploy (Blueprint §10, §17.2). */
class PlanController extends Controller
{
    public function index()
    {
        return view('admin.plans', ['plans' => Plan::orderBy('sort_order')->withCount(['subscriptions' => fn ($q) => $q->where('status', 'active')])->get()]);
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'price' => ['required', 'numeric', 'min:0'],
            'listing_limit' => ['nullable', 'integer', 'min:0'],
            'staff_limit' => ['nullable', 'integer', 'min:1'],
            'branch_limit' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:active,inactive'],
        ]);
        $before = $plan->only(['name', 'price', 'limits_json', 'status']);
        $plan->update([
            'name' => $data['name'], 'price' => $data['price'], 'status' => $data['status'],
            'limits_json' => [
                'listing_limit' => $data['listing_limit'] ?? null,
                'staff_limit' => $data['staff_limit'] ?? null,
                'branch_limit' => $data['branch_limit'] ?? null,
            ],
        ]);
        Audit::log('platform.plan_updated', $plan, $before, $plan->only(['name', 'price', 'limits_json', 'status']));

        return back()->with('status', 'Paket diperbarui.');
    }
}
