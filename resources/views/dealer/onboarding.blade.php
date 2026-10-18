@extends('layouts.app', ['pageTitle' => 'Daftarkan dealer', 'noindex' => true])
@section('content')
<div class="container section" style="max-width:760px">
    <h1>Daftarkan dealer Anda</h1>
    <p class="muted">Kelola inventaris unit, iklan, leads, booking, dan staf dalam satu dashboard. Mulai dengan paket Gratis; tingkatkan kapan saja.</p>
    @if ($hasTenant)
        <div class="notice notice-info mb-2"><x-icon name="info" /><div>Anda sudah tergabung di dealer. <a href="{{ route('dealer.dashboard') }}">Buka dashboard</a> atau buat organisasi dealer baru di bawah.</div></div>
    @endif
    <ol class="checklist mb-2 small">
        <li class="done"><span class="dot"><x-icon name="check" class="icon icon-sm" /></span>Akun terdaftar</li>
        <li><span class="dot">2</span>Profil dealer & cabang utama</li>
        <li><span class="dot">3</span>Undang staf, tambah unit, ajukan iklan pertama</li>
    </ol>
    <form method="post" action="{{ route('dealer.onboarding.store') }}" class="card card-body stack">
        @csrf
        <div class="form-grid cols-2">
            <x-field name="name" label="Nama dealer/showroom" required placeholder="Nama usaha" />
            <x-field name="legal_name" label="Nama badan usaha (opsional)" placeholder="PT/CV" />
            <x-field name="phone" label="Telepon" type="tel" required placeholder="08xxxxxxxxxx" />
            <x-field name="whatsapp" label="WhatsApp (opsional)" type="tel" placeholder="08xxxxxxxxxx" />
            <x-field name="email" label="Email usaha (opsional)" type="email" />
            <x-field name="city_id" label="Kota" type="select" required>
                <option value="">Pilih kota</option>@foreach ($cities as $c)<option value="{{ $c->id }}" @selected(old('city_id') == $c->id)>{{ $c->name }}</option>@endforeach
            </x-field>
            <x-field name="address" label="Alamat cabang utama" required class="span-2" />
            <x-field name="description" label="Deskripsi singkat" type="textarea" class="span-2" />
        </div>
        <p class="small muted mb-0">Badge “Dealer terverifikasi” hanya diberikan setelah tim platform memeriksa dokumen usaha Anda.</p>
        <button class="btn btn-primary">Buat dealer</button>
    </form>
</div>
@endsection
