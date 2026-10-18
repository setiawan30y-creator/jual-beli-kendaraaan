@extends('layouts.app', ['pageTitle' => 'Akun saya', 'noindex' => true])
@section('content')
<div class="container section">
    <div class="row-between"><h1 class="mb-0">Halo, {{ $user->name }}</h1>
        <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-outline btn-sm"><x-icon name="logout" class="icon icon-sm" />Keluar</button></form></div>
    @include('account._nav')
    <div class="grid grid-2">
        <div class="stack">
            <div class="stat-grid" style="grid-template-columns:repeat(2,1fr)">
                <a class="card stat" href="{{ route('account.favorites') }}" style="color:inherit"><div class="label">Favorit</div><div class="value">{{ $favoriteCount }}</div></a>
                <a class="card stat" href="{{ route('account.listings') }}" style="color:inherit"><div class="label">Iklan saya</div><div class="value">{{ $listingCount }}</div></a>
            </div>
            <div class="card card-body">
                <h2 class="card-title">Dealer & panel</h2>
                @forelse ($memberships as $m)
                    <div class="row-between mb-2"><span><strong>{{ $m->tenant->name }}</strong><br><span class="small muted">{{ $m->roleLabel() }}</span></span>
                        <form method="post" action="{{ route('dealer.switch', $m->tenant_id) }}">@csrf<button class="btn btn-primary btn-sm">Buka dashboard</button></form></div>
                @empty
                    <p class="muted">Anda belum tergabung di dealer mana pun.</p>
                    <a class="btn btn-outline btn-sm" href="{{ route('dealer.onboarding') }}"><x-icon name="store" class="icon icon-sm" />Daftarkan dealer</a>
                @endforelse
                @if ($user->isPlatformStaff())
                    <hr class="divider"><a class="btn btn-outline btn-sm" href="{{ route('admin.dashboard') }}"><x-icon name="shield" class="icon icon-sm" />Panel admin platform</a>
                @endif
            </div>
            <div class="card card-body">
                <h2 class="card-title">Jadwal terbaru</h2>
                @forelse ($bookings as $b)
                    <div class="row-between small mb-2"><span>{{ $b->listing?->title }}<br><span class="muted">{{ $b->starts_at->format('d/m/Y H:i') }}</span></span><x-status-badge :status="$b->status" :label="\App\Models\Booking::STATUSES[$b->status]" /></div>
                @empty<p class="muted mb-0">Belum ada jadwal kunjungan.</p>@endforelse
            </div>
        </div>
        <div class="stack">
            <form class="card card-body stack" method="post" action="{{ route('account.profile') }}">
                @csrf @method('patch')
                <h2 class="card-title mb-0">Profil</h2>
                <x-field name="name" label="Nama" :value="$user->name" required />
                <x-field name="phone" label="Telepon/WhatsApp" type="tel" :value="$user->phone" />
                <div class="field"><span class="label">Email</span><span>{{ $user->email }}</span></div>
                <button class="btn btn-primary">Simpan profil</button>
            </form>
            <form class="card card-body stack" method="post" action="{{ route('account.theme') }}">
                @csrf @method('patch')
                <h2 class="card-title mb-0"><x-icon name="palette" class="icon icon-sm" /> Mode tampilan</h2>
                <div class="segmented" role="radiogroup" aria-label="Mode tampilan">
                    @foreach (['light' => 'Terang', 'dark' => 'Gelap', 'system' => 'Ikuti sistem'] as $k => $v)
                        <label><input type="radio" name="theme_mode" value="{{ $k }}" @checked($user->theme_mode === $k)><span>{{ $v }}</span></label>
                    @endforeach
                </div>
                <p class="small muted mb-0">Mode tampilan tidak mengubah warna merek dealer.</p>
                <button class="btn btn-outline">Simpan mode</button>
            </form>
        </div>
    </div>
</div>
@endsection
