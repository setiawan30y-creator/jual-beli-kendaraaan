@extends('layouts.app', ['pageTitle' => 'Pasang iklan', 'canonical' => route('sell.create')])
@section('content')
<div class="container section" style="max-width:860px">
    <h1>Pasang iklan kendaraan</h1>
    @guest
        <div class="card card-body stack">
            <p>Pasang iklan motor atau mobil Anda sebagai <strong>penjual perorangan</strong> (maksimal {{ config('marketplace.private_seller_listing_limit') }} iklan aktif). Setiap iklan ditinjau moderator sebelum tayang.</p>
            <div class="row"><a class="btn btn-primary" href="{{ route('login') }}">Masuk untuk melanjutkan</a><a class="btn btn-outline" href="{{ route('register') }}">Daftar akun</a></div>
            <hr class="divider">
            <p class="mb-0">Punya dealer atau showroom? <a href="{{ route('dealer.onboarding') }}">Daftarkan dealer</a> untuk mengelola stok, leads, dan staf.</p>
        </div>
    @else
    <form method="post" action="{{ route('sell.store') }}" enctype="multipart/form-data" class="stack">
        @csrf
        <fieldset class="fieldset">
            <legend>1. Data kendaraan</legend>
            <div class="form-grid cols-2">
                <x-field name="model_id" label="Merek & model" type="select" required class="span-2">
                    <option value="">Pilih model</option>
                    @foreach ($models->groupBy('category') as $cat => $group)
                        <optgroup label="{{ ucfirst($cat) }}">@foreach ($group as $m)<option value="{{ $m->id }}" @selected(old('model_id') == $m->id)>{{ $m->fullName() }}</option>@endforeach</optgroup>
                    @endforeach
                </x-field>
                <x-field name="condition" label="Kondisi" type="select" required>
                    <option value="used" @selected(old('condition', 'used') === 'used')>Seken</option>
                    <option value="new" @selected(old('condition') === 'new')>Baru</option>
                </x-field>
                <x-field name="year" label="Tahun" type="number" required min="1980" :max="now()->year + 1" :value="now()->year" />
                <x-field name="odometer_km" label="Kilometer" type="number" min="0" placeholder="Contoh: 15000" />
                <x-field name="color" label="Warna" placeholder="Contoh: Hitam" />
                <x-field name="frame_number" label="Nomor rangka (privat)" hint="Disimpan terenkripsi, tidak ditampilkan publik." />
                <x-field name="engine_number" label="Nomor mesin (privat)" hint="Disimpan terenkripsi, tidak ditampilkan publik." />
            </div>
        </fieldset>
        <fieldset class="fieldset">
            <legend>2. Iklan</legend>
            <div class="form-grid cols-2">
                <x-field name="title" label="Judul iklan" required maxlength="120" placeholder="Contoh: Matic 2022 pajak hidup, tangan pertama" class="span-2" />
                <x-field name="price" label="Harga (Rp)" type="number" required min="100000" step="50000" />
                <x-field name="city_id" label="Kota" type="select" required>
                    <option value="">Pilih kota</option>
                    @foreach ($cities as $c)<option value="{{ $c->id }}" @selected(old('city_id') == $c->id)>{{ $c->name }}</option>@endforeach
                </x-field>
                <x-field name="description" label="Deskripsi" type="textarea" class="span-2" placeholder="Kondisi mesin & bodi, riwayat servis, kelengkapan dokumen, alasan dijual." />
            </div>
        </fieldset>
        <fieldset class="fieldset">
            <legend>3. Foto unit</legend>
            <p class="small muted">Wajib: tampak depan. Disarankan: belakang, samping kanan & kiri. Format JPG/PNG/WebP, maks. {{ config('marketplace.uploads.max_kb') / 1024 }} MB per foto. Dokumen BPKB/STNK dan nomor rangka dapat diunggah setelah draf tersimpan (privat, hanya untuk verifikasi).</p>
            <div class="uploader">
                @foreach (['depan', 'belakang', 'kanan', 'kiri'] as $cat)
                    <div class="slot">
                        <div class="slot-head"><span><strong>{{ $categories[$cat]['label'] }}</strong><span class="small muted">{{ $categories[$cat]['hint'] }}</span></span>@if($cat === 'depan')<span class="badge badge-warning">Wajib</span>@endif</div>
                        <input class="file-input" type="file" name="photos[{{ $cat }}]" accept="image/jpeg,image/png,image/webp" data-preview="pv-{{ $cat }}" data-max-kb="{{ config('marketplace.uploads.max_kb') }}" @required($cat === 'depan') aria-label="Unggah {{ $categories[$cat]['label'] }}">
                        <div class="slot-preview" id="pv-{{ $cat }}" aria-live="polite"></div>
                        @error('photos.'.$cat)<span class="error-text">{{ $message }}</span>@enderror
                    </div>
                @endforeach
            </div>
            <label class="check mt-2"><input type="checkbox" name="publish_consent" value="1" required> <span>Saya mengizinkan foto unit di atas ditampilkan publik pada iklan setelah disetujui moderator.</span></label>
        </fieldset>
        <div class="row"><button class="btn btn-primary">Simpan draf iklan</button><span class="small muted">Draf belum tayang sampai Anda mengajukan dan moderator menyetujui.</span></div>
    </form>
    @endguest
</div>
@endsection
