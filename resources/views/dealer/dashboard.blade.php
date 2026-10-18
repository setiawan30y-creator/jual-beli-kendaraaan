@extends('layouts.dealer')
@section('heading', 'Ringkasan')
@section('widgets')
    {{-- Widget Cuaca & Lokasi (Blueprint: header dashboard) --}}
    <span class="widget" title="Lokasi cabang aktif"><x-icon name="pin" /><span>{{ $branch?->city?->name ?? 'Lokasi belum diatur' }}</span><span class="w-detail muted">· {{ $branch?->name }}</span></span>
    @if ($weather)
        <span class="widget" title="Cuaca di {{ $branch?->city?->name }}, diperbarui {{ $weather['updated_at'] }}">
            <x-icon :name="match($weather['icon']) { 'sun' => 'sun', 'rain', 'drizzle', 'storm' => 'rain', default => 'cloud' }" />
            <span>{{ $weather['temperature'] }}{{ $weather['unit'] }}</span><span class="w-detail muted">{{ $weather['label'] }} · {{ $weather['updated_at'] }}</span>
        </span>
    @else
        <span class="widget" title="Data cuaca tidak tersedia"><x-icon name="cloud" /><span class="muted">Cuaca tidak tersedia</span></span>
    @endif
@endsection
@section('content')
<div class="row-between mb-2">
    <h1 class="mb-0">{{ $currentTenant->name }}</h1>
    @if (app(\App\Support\TenantContext::class)->can('units.manage'))<a class="btn btn-primary" href="{{ route('dealer.units.create') }}"><x-icon name="add" />Tambah unit</a>@endif
</div>
@if (! $currentTenant->isVerified())
    <div class="notice notice-info mb-2"><x-icon name="verified" /><div>Dealer belum terverifikasi. Lengkapi profil; tim platform akan memeriksa sebelum badge verifikasi ditampilkan.</div></div>
@endif
<div class="stat-grid mb-2">
    <a class="card stat" href="{{ route('dealer.units.index', ['status' => 'available']) }}" style="color:inherit"><div class="label">Unit tersedia</div><div class="value">{{ $units['available'] ?? 0 }}</div></a>
    <a class="card stat" href="{{ route('dealer.units.index', ['status' => 'reserved']) }}" style="color:inherit"><div class="label">Dipesan</div><div class="value">{{ $units['reserved'] ?? 0 }}</div></a>
    <a class="card stat" href="{{ route('dealer.listings.index', ['status' => 'published']) }}" style="color:inherit"><div class="label">Iklan tayang</div><div class="value">{{ $activeListings }}</div></a>
    <div class="card stat"><div class="label">Terjual bulan ini</div><div class="value">{{ $soldThisMonth }}</div></div>
    <a class="card stat" href="{{ route('dealer.leads.index', ['status' => 'baru']) }}" style="color:inherit"><div class="label">Leads baru</div><div class="value">{{ $newLeads }}</div></a>
    <div class="card stat"><div class="label">Follow-up jatuh tempo</div><div class="value" style="{{ $followUps ? 'color:var(--warning)' : '' }}">{{ $followUps }}</div></div>
    <a class="card stat" href="{{ route('dealer.bookings.index') }}" style="color:inherit"><div class="label">Booking menunggu</div><div class="value">{{ $bookings }}</div></a>
    <div class="card stat"><div class="label">Menunggu review</div><div class="value">{{ $pendingListings }}</div></div>
</div>
<div class="grid grid-2">
    <div class="card card-body">
        <div class="row-between"><h2 class="card-title">Leads terbaru</h2><a class="small" href="{{ route('dealer.leads.index') }}">Semua</a></div>
        @forelse ($recentLeads as $l)
            <a class="row-between mb-2" href="{{ route('dealer.leads.show', $l) }}" style="color:inherit">
                <span><strong>{{ $l->buyer_name }}</strong><br><span class="small muted">{{ $l->listing?->title }} · {{ $l->created_at->diffForHumans() }}</span></span>
                <x-status-badge :status="$l->status" :label="\App\Models\Lead::STATUSES[$l->status]" />
            </a>
        @empty<p class="muted mb-0">Belum ada lead.</p>@endforelse
    </div>
    <div class="stack">
        <div class="card card-body">
            <h2 class="card-title">Kuota paket</h2>
            @foreach ($usage as $u)
                @php $pct = $u['limit'] ? min(100, round($u['used'] / max($u['limit'], 1) * 100)) : 0; @endphp
                <div class="mb-2"><div class="row-between small"><span>{{ $u['label'] }}</span><span>{{ $u['used'] }} / {{ $u['limit'] ?? '∞' }}</span></div>
                    <div class="progress {{ $pct >= 100 ? 'is-danger' : ($pct >= 80 ? 'is-warning' : '') }}" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $u['label'] }}"><span style="width:{{ $pct }}%"></span></div></div>
            @endforeach
            <a class="small" href="{{ route('dealer.billing') }}">Kelola paket</a>
        </div>
        <div class="card card-body">
            <h2 class="card-title">Checklist onboarding</h2>
            <ul class="checklist">
                @foreach ($onboarding as $step)
                    <li class="{{ $step['done'] ? 'done' : '' }}"><span class="dot">@if($step['done'])<x-icon name="check" class="icon icon-sm" />@endif</span>{{ $step['label'] }}<span class="sr-only">{{ $step['done'] ? '(selesai)' : '(belum)' }}</span></li>
                @endforeach
            </ul>
        </div>
        <div class="card card-body">
            <h2 class="card-title">Jadwal mendatang</h2>
            @forelse ($upcomingBookings as $b)
                <div class="row-between small mb-2"><span>{{ $b->buyer?->name }} — {{ $b->listing?->title }}<br><span class="muted">{{ \App\Models\Booking::TYPES[$b->booking_type] }} · {{ $b->starts_at->format('d/m H:i') }}</span></span><x-status-badge :status="$b->status" :label="\App\Models\Booking::STATUSES[$b->status]" /></div>
            @empty<p class="muted mb-0">Tidak ada jadwal mendatang.</p>@endforelse
        </div>
    </div>
</div>
@endsection
