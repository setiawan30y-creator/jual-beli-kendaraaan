<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\City;
use App\Models\TenantBranch;
use App\Services\PlanLimits;
use App\Support\Audit;
use App\Support\Color;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function __construct(protected TenantContext $ctx) {}

    public function edit()
    {
        $this->ctx->authorize('settings.manage');

        return view('dealer.settings', [
            'tenant' => $this->ctx->tenant()->load('branches.city'),
            'cities' => City::orderBy('name')->get(),
            'presets' => config('marketplace.theme_presets'),
            'theme' => $this->ctx->tenant()->theme(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $this->ctx->authorize('settings.manage');
        $tenant = $this->ctx->tenant();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'legal_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'whatsapp' => ['nullable', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'email' => ['nullable', 'email'],
            'city_id' => ['required', 'exists:cities,id'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        $before = $tenant->only(array_keys($data));
        $tenant->update($data);
        Audit::log('tenant.profile_updated', $tenant, $before, $tenant->only(array_keys($data)), null, $tenant->id);

        return back()->with('status', 'Profil dealer disimpan.');
    }

    /** Pusat Pengaturan Tema (Blueprint §17.4.1): preset/brand color dengan validasi kontras. */
    public function updateTheme(Request $request)
    {
        $this->ctx->authorize('settings.manage');
        $tenant = $this->ctx->tenant();
        if ($request->boolean('reset')) {
            $theme = config('marketplace.theme_default');
        } else {
            $data = $request->validate([
                'primary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            ], [], ['primary' => 'warna utama', 'accent' => 'warna aksen']);
            if (Color::contrast($data['primary'], '#ffffff') < 4.5) {
                return back()->withErrors(['primary' => 'Warna utama terlalu terang: teks putih di atasnya tidak terbaca (kontras minimal 4.5:1).'])->withInput();
            }
            $theme = ['primary' => strtolower($data['primary']), 'accent' => strtolower($data['accent'])];
        }
        $settings = $tenant->settings_json ?? [];
        $before = $settings['theme'] ?? null;
        $settings['theme'] = $theme;
        $tenant->settings_json = $settings;
        $tenant->save();
        Audit::log('tenant.theme_updated', $tenant, ['theme' => $before], ['theme' => $theme], null, $tenant->id);

        return back()->with('status', 'Tema disimpan.');
    }

    public function storeBranch(Request $request, PlanLimits $limits)
    {
        $this->ctx->authorize('settings.manage');
        $tenant = $this->ctx->tenant();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'city_id' => ['required', 'exists:cities,id'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);
        if (! $limits->canConsume($tenant, 'branch_limit')) {
            return back()->withErrors(['name' => 'Kuota cabang pada paket Anda sudah penuh.']);
        }
        $city = City::find($data['city_id']);
        $branch = new TenantBranch($data + ['latitude' => $city->latitude, 'longitude' => $city->longitude]);
        $branch->tenant_id = $tenant->id;
        $branch->save();
        Audit::log('branch.created', $branch, null, ['name' => $branch->name], null, $tenant->id);

        return back()->with('status', 'Cabang ditambahkan.');
    }

    public function audit()
    {
        $this->ctx->authorize('settings.manage');
        $logs = AuditLog::where('tenant_id', $this->ctx->id())->with('actor')->latest('id')->paginate(30);

        return view('dealer.audit', compact('logs'));
    }
}
