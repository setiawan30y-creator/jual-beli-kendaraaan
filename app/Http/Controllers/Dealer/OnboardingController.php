<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantBranch;
use App\Models\TenantMembership;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Onboarding dealer: buat tenant, cabang utama, keanggotaan owner, paket gratis (Blueprint UI §25). */
class OnboardingController extends Controller
{
    public function create(Request $request)
    {
        return view('dealer.onboarding', [
            'cities' => City::orderBy('name')->get(),
            'hasTenant' => $request->user()->memberships()->where('status', 'active')->exists(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'legal_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'whatsapp' => ['nullable', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'email' => ['nullable', 'email'],
            'city_id' => ['required', 'exists:cities,id'],
            'address' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [], ['name' => 'nama dealer', 'city_id' => 'kota', 'address' => 'alamat cabang utama']);

        $user = $request->user();
        $tenant = DB::transaction(function () use ($data, $user) {
            $tenant = new Tenant(collect($data)->except('address')->all());
            $tenant->slug = $this->uniqueSlug($data['name']);
            $tenant->owner_user_id = $user->id;
            $tenant->status = 'active';
            $tenant->verification_status = 'unverified';
            $tenant->save();

            $city = City::find($data['city_id']);
            $branch = new TenantBranch([
                'name' => 'Cabang Utama', 'address' => $data['address'], 'city_id' => $city->id,
                'latitude' => $city->latitude, 'longitude' => $city->longitude, 'phone' => $data['phone'], 'is_default' => true,
            ]);
            $branch->tenant_id = $tenant->id;
            $branch->save();

            TenantMembership::create([
                'tenant_id' => $tenant->id, 'user_id' => $user->id, 'branch_id' => $branch->id,
                'role' => 'tenant_owner', 'status' => 'active', 'joined_at' => now(),
            ]);

            $plan = Plan::where('code', 'gratis')->first() ?? Plan::orderBy('price')->first();
            if ($plan) {
                $tenant->subscriptions()->create([
                    'plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now(),
                    'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
                ]);
            }
            $user->forceFill(['current_tenant_id' => $tenant->id])->save();
            Audit::log('tenant.created', $tenant, null, ['name' => $tenant->name], null, $tenant->id);

            return $tenant;
        });

        return redirect()->route('dealer.dashboard')->with('status', "Dealer {$tenant->name} siap. Tambahkan unit pertama Anda.");
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'dealer';
        $slug = $base;
        $i = 2;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
