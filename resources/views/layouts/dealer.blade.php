@php
    $ctx = app(\App\Support\TenantContext::class);
    $r = fn ($n) => request()->routeIs($n);
    $items = [
        ['dealer.dashboard', 'Ringkasan', 'dashboard', 'dashboard.view'],
        ['group' => 'Operasional'],
        ['dealer.units.index', 'Inventaris unit', 'moto', 'units.view', 'dealer.units.*'],
        ['dealer.listings.index', 'Iklan', 'tag', 'units.view', 'dealer.listings.*'],
        ['dealer.leads.index', 'Leads / CRM', 'chat', 'leads.manage', 'dealer.leads.*'],
        ['dealer.parties.index', 'Nasabah & pemasok', 'people', 'parties.view', 'dealer.parties.*'],
        ['dealer.bookings.index', 'Booking & test ride', 'event', 'bookings.manage', 'dealer.bookings.*'],
        ['group' => 'Organisasi'],
        ['dealer.members.index', 'Anggota & peran', 'user', 'members.manage', 'dealer.members.*'],
        ['dealer.billing', 'Paket & langganan', 'receipt', 'dashboard.view'],
        ['dealer.settings', 'Pengaturan & tema', 'settings', 'settings.manage'],
        ['dealer.audit', 'Log audit', 'history', 'settings.manage'],
    ];
    $panelNav = [];
    foreach ($items as $it) {
        if (isset($it['group'])) { $panelNav[] = $it; continue; }
        if (! $ctx->can($it[3])) continue;
        $panelNav[] = ['url' => route($it[0]), 'label' => $it[1], 'icon' => $it[2], 'active' => $r($it[4] ?? $it[0])];
    }
    $panelTheme = $currentTenant->theme();
@endphp
@extends('layouts.panel')

@section('sidebar-top')
    <div class="tenant-switch mt-2">
        <span class="avatar" style="width:40px;height:40px">{{ mb_substr($currentTenant->name, 0, 1) }}</span>
        <div style="min-width:0">
            <strong style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $currentTenant->name }}</strong>
            <span class="small muted">{{ $currentMembership->roleLabel() }}</span>
        </div>
    </div>
    @if ($userMemberships->count() > 1)
        <details class="mt-1" style="padding:0 6px">
            <summary class="small" style="cursor:pointer">Ganti dealer</summary>
            @foreach ($userMemberships->where('tenant_id', '!=', $currentTenant->id) as $m)
                <form method="post" action="{{ route('dealer.switch', $m->tenant_id) }}">@csrf<button class="btn btn-text btn-sm">{{ $m->tenant->name }}</button></form>
            @endforeach
        </details>
    @endif
@endsection

@section('header-widgets')
    @yield('widgets')
@endsection
