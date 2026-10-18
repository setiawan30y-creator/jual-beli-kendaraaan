@extends('layouts.dealer')
@section('heading', 'Pengaturan')
@section('content')
<h1>Pengaturan</h1>
<div class="grid grid-2">
    <div class="stack">
        <form method="post" action="{{ route('dealer.settings.profile') }}" class="card card-body stack">
            @csrf @method('put')
            <h2 class="card-title mb-0">Profil dealer</h2>
            <x-field name="name" label="Nama dealer" :value="$tenant->name" required />
            <x-field name="legal_name" label="Nama badan usaha" :value="$tenant->legal_name" />
            <div class="form-grid cols-2">
                <x-field name="phone" label="Telepon" type="tel" :value="$tenant->phone" required />
                <x-field name="whatsapp" label="WhatsApp" type="tel" :value="$tenant->whatsapp" />
            </div>
            <x-field name="email" label="Email" type="email" :value="$tenant->email" />
            <x-field name="city_id" label="Kota" type="select" required>@foreach ($cities as $c)<option value="{{ $c->id }}" @selected($tenant->city_id === $c->id)>{{ $c->name }}</option>@endforeach</x-field>
            <x-field name="description" label="Deskripsi" type="textarea" :value="$tenant->description" />
            <div class="row"><button class="btn btn-primary">Simpan profil</button><a class="btn btn-text" href="{{ route('dealers.show', $tenant) }}" target="_blank">Lihat halaman publik</a></div>
        </form>
        <div class="card card-body">
            <h2 class="card-title">Cabang</h2>
            <ul class="checklist mb-2">
                @foreach ($tenant->branches as $b)<li><x-icon name="pin" class="icon icon-sm" /><span><strong>{{ $b->name }}</strong>@if($b->is_default) <span class="badge">Utama</span>@endif<br><span class="small muted">{{ $b->address }}, {{ $b->city?->name }}</span></span></li>@endforeach
            </ul>
            <details><summary class="btn btn-outline btn-sm">Tambah cabang</summary>
                <form method="post" action="{{ route('dealer.settings.branches.store') }}" class="stack mt-2">@csrf
                    <x-field name="name" label="Nama cabang" required />
                    <x-field name="address" label="Alamat" required />
                    <x-field name="city_id" label="Kota" type="select" required>@foreach ($cities as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</x-field>
                    <x-field name="phone" label="Telepon" type="tel" />
                    <button class="btn btn-primary btn-sm">Simpan cabang</button>
                </form></details>
        </div>
    </div>

    {{-- Pusat Pengaturan Tema (Blueprint §17.4.1) --}}
    <form method="post" action="{{ route('dealer.settings.theme') }}" class="card card-body stack" data-theme-form>
        @csrf @method('put')
        <h2 class="card-title mb-0"><x-icon name="palette" class="icon icon-sm" /> Tampilan & tema</h2>
        <p class="small muted mb-0">Warna merek dealer dipakai di dashboard dan halaman publik dealer. Mode terang/gelap diatur masing-masing pengguna di halaman Akun.</p>
        <div class="field"><span class="label">Preset</span>
            <div class="swatches">
                @foreach ($presets as $k => $p)
                    <label class="swatch"><input type="radio" name="preset" value="{{ $k }}" data-preset data-primary="{{ $p['primary'] }}" data-accent="{{ $p['accent'] }}" @checked($theme['primary'] === $p['primary'])>
                        <span class="sw"><span style="background:{{ $p['primary'] }}"></span><span style="background:{{ $p['accent'] }}"></span></span>{{ $p['label'] }}</label>
                @endforeach
            </div>
        </div>
        <div class="form-grid cols-2">
            <div class="field"><label for="f_primary">Warna utama</label><input id="f_primary" class="input" type="color" name="primary" value="{{ old('primary', $theme['primary']) }}" style="padding:4px;height:46px"></div>
            <div class="field"><label for="f_accent">Warna aksen</label><input id="f_accent" class="input" type="color" name="accent" value="{{ old('accent', $theme['accent']) }}" style="padding:4px;height:46px"></div>
        </div>
        <div class="notice notice-warning" data-contrast-warning hidden><x-icon name="warning" /><div>Warna utama terlalu terang untuk teks putih. Pilih warna lebih gelap agar tetap terbaca.</div></div>
        <div class="theme-preview" data-theme-preview aria-label="Pratinjau tema">
            <div class="tp-head">Pratinjau header</div>
            <div class="tp-body">
                <div class="row"><span class="btn btn-sm" style="background:var(--tp-primary);color:var(--tp-on)">Tombol utama</span><span class="btn btn-sm btn-outline" style="color:var(--tp-primary)">Sekunder</span><span class="badge" style="background:var(--tp-accent);color:#111827">Aksen</span></div>
                <div class="row small"><span class="badge badge-success"><x-icon name="check" />Berhasil</span><span class="badge badge-warning"><x-icon name="warning" />Peringatan</span><span class="badge badge-danger"><x-icon name="close" />Gagal</span></div>
                <input class="input" placeholder="Contoh input" aria-label="Contoh input" readonly>
            </div>
        </div>
        <div class="row"><button class="btn btn-primary">Simpan tema</button><button class="btn btn-outline" name="reset" value="1">Reset ke default</button></div>
    </form>
</div>
@endsection
