@extends('layouts.admin')
@section('heading', $tenant->name)
@section('content')
<nav class="breadcrumb"><a href="{{ route('admin.tenants.index') }}">Tenant</a><span>›</span><span>{{ $tenant->name }}</span></nav>
<div class="row-between mb-2"><h1 class="mb-0">{{ $tenant->name }}</h1><div class="row"><x-status-badge :status="$tenant->status" /><x-status-badge :status="$tenant->verification_status" /></div></div>
<div class="detail">
    <div class="stack">
        <div class="card card-body">
            <dl class="kv">
                <dt>Pemilik</dt><dd>{{ $tenant->owner->name }} ({{ $tenant->owner->email }})</dd>
                <dt>Badan usaha</dt><dd>{{ $tenant->legal_name ?? '—' }}</dd>
                <dt>Kontak</dt><dd>{{ $tenant->phone }} · {{ $tenant->email }}</dd>
                <dt>Paket</dt><dd>{{ $tenant->activeSubscription?->plan?->name ?? '—' }}</dd>
                @if ($tenant->status_reason)<dt>Alasan status</dt><dd>{{ $tenant->status_reason }}</dd>@endif
            </dl>
        </div>
        <div class="card card-body"><h2 class="card-title">Penggunaan</h2>
            @foreach ($usage as $u)<div class="row-between small"><span>{{ $u['label'] }}</span><span>{{ $u['used'] }} / {{ $u['limit'] ?? '∞' }}</span></div>@endforeach</div>
        <div class="card card-body"><h2 class="card-title">Anggota</h2>
            @foreach ($tenant->memberships as $m)<div class="row-between small mb-2"><span>{{ $m->user->name }} · {{ $m->user->email }}</span><span>{{ $m->roleLabel() }} · {{ $m->status }}</span></div>@endforeach</div>
        <h2>Log audit tenant</h2>
        @include('partials.audit-table', ['logs' => $logs, 'showTenant' => false])
    </div>
    <aside class="detail-aside stack">
        <form method="post" action="{{ route('admin.tenants.status', $tenant->id) }}" class="card card-body stack" data-confirm="Ubah status tenant ini?">@csrf
            <h2 class="card-title mb-0">Status tenant</h2>
            <x-field name="status" label="Status baru" type="select">
                @foreach (['active' => 'Aktif', 'suspended' => 'Tangguhkan', 'closed' => 'Tutup'] as $k => $v)<option value="{{ $k }}" @selected($tenant->status === $k)>{{ $v }}</option>@endforeach
            </x-field>
            <x-field name="reason" label="Alasan (wajib, tercatat)" required />
            <p class="small muted mb-0">Data tenant tidak dihapus saat ditangguhkan atau ditutup.</p>
            <button class="btn btn-outline">Simpan status</button>
        </form>
        <form method="post" action="{{ route('admin.tenants.verify', $tenant->id) }}" class="card card-body stack">@csrf
            <h2 class="card-title mb-0">Verifikasi dealer</h2>
            <x-field name="verification_status" label="Status verifikasi" type="select">
                @foreach (['unverified' => 'Belum diverifikasi', 'pending' => 'Dalam review', 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak'] as $k => $v)<option value="{{ $k }}" @selected($tenant->verification_status === $k)>{{ $v }}</option>@endforeach
            </x-field>
            <x-field name="reason" label="Dasar keputusan" required />
            <button class="btn btn-primary">Simpan verifikasi</button>
        </form>
    </aside>
</div>
@endsection
