@php
    $u = auth()->user();
    $r = fn ($n) => request()->routeIs($n);
    $panelNav = [['url' => route('admin.dashboard'), 'label' => 'Dashboard platform', 'icon' => 'dashboard', 'active' => $r('admin.dashboard')]];
    if ($u->canModerate()) {
        $panelNav[] = ['group' => 'Trust & safety'];
        $panelNav[] = ['url' => route('admin.moderation'), 'label' => 'Moderasi iklan', 'icon' => 'shield', 'active' => $r('admin.moderation*')];
        $panelNav[] = ['url' => route('admin.reports'), 'label' => 'Laporan iklan', 'icon' => 'flag', 'active' => $r('admin.reports*')];
    }
    if ($u->isPlatformAdmin()) {
        $panelNav[] = ['group' => 'Platform'];
        $panelNav[] = ['url' => route('admin.tenants.index'), 'label' => 'Tenant / dealer', 'icon' => 'store', 'active' => $r('admin.tenants.*')];
        $panelNav[] = ['url' => route('admin.users.index'), 'label' => 'Pengguna', 'icon' => 'people', 'active' => $r('admin.users.*')];
        $panelNav[] = ['url' => route('admin.catalog'), 'label' => 'Master katalog', 'icon' => 'book', 'active' => $r('admin.catalog*')];
        $panelNav[] = ['url' => route('admin.plans'), 'label' => 'Paket & limit', 'icon' => 'receipt', 'active' => $r('admin.plans*')];
        $panelNav[] = ['url' => route('admin.audit'), 'label' => 'Log audit', 'icon' => 'history', 'active' => $r('admin.audit')];
    }
@endphp
@extends('layouts.panel')
@section('sidebar-top')
    <div class="tenant-switch mt-2"><span class="avatar" style="width:40px;height:40px"><x-icon name="shield" /></span><div><strong>Admin platform</strong><br><span class="small muted">{{ str_replace('_', ' ', $u->platform_role) }}</span></div></div>
@endsection
