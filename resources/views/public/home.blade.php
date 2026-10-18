@extends('layouts.app', ['canonical' => route('home')])

@section('content')
<section class="hero">
    <div class="container">
        <h1>Temukan motor & mobil impian, baru atau seken.</h1>
        <p class="lead">Bandingkan harga, cek kondisi dan status inspeksi, lalu hubungi dealer terverifikasi atau penjual perorangan secara langsung.</p>
        <div class="card search-panel">
            <form action="{{ route('catalog') }}" method="get" class="stack" role="search">
                <div class="segmented" role="radiogroup" aria-label="Kategori">
                    <label><input type="radio" name="kategori" value="motor" checked><span>Motor</span></label>
                    <label><input type="radio" name="kategori" value="mobil"><span>Mobil</span></label>
                </div>
                <div class="search-form">
                    <div class="field"><label for="q">Merek atau model</label><input id="q" class="input" name="q" placeholder="Contoh: Vario, NMAX, Avanza" autocomplete="off"></div>
                    <div class="field"><label for="kondisi">Kondisi</label>
                        <select id="kondisi" class="select" name="kondisi"><option value="">Baru & seken</option><option value="baru">Baru</option><option value="seken">Seken</option></select>
                    </div>
                    <div class="field"><label for="kota">Lokasi (opsional)</label>
                        <select id="kota" class="select" name="kota"><option value="">Semua kota</option>@foreach (\App\Models\City::orderBy('name')->get() as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
                    </div>
                    <button class="btn btn-primary"><x-icon name="search" />Cari</button>
                </div>
            </form>
        </div>
    </div>
</section>

<section class="section" style="padding-top:12px">
    <div class="container">
        <div class="shortcut-grid">
            <a class="shortcut" href="{{ route('catalog.motor.new') }}"><span class="ic"><x-icon name="moto" /></span>Motor Baru</a>
            <a class="shortcut" href="{{ route('catalog.motor.used') }}"><span class="ic"><x-icon name="moto" /></span>Motor Seken</a>
            <a class="shortcut" href="{{ route('catalog.car') }}"><span class="ic"><x-icon name="car" /></span>Mobil</a>
            <a class="shortcut" href="{{ route('dealers.index') }}"><span class="ic"><x-icon name="store" /></span>Dealer</a>
            <a class="shortcut" href="{{ route('pages.trade-in') }}"><span class="ic"><x-icon name="swap" /></span>Tukar Tambah</a>
            <a class="shortcut" href="{{ route('pages.credit') }}"><span class="ic"><x-icon name="calc" /></span>Simulasi Kredit</a>
        </div>

        <div class="card install-card mt-2" data-install-banner>
            <span class="ic"><x-icon name="download" /></span>
            <div style="flex:1">
                <strong>Pasang aplikasi {{ $brandName }}</strong>
                <div class="small muted">Buka lebih cepat dari layar utama HP dan jelajahi kendaraan kapan saja saat terhubung ke internet.</div>
            </div>
            <div class="row">
                <button class="btn btn-primary btn-sm" type="button" data-install-trigger>Instal Aplikasi</button>
                <button class="btn btn-text btn-sm" type="button" data-install-later>Nanti</button>
            </div>
        </div>
    </div>
</section>

@if ($brands->isNotEmpty())
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-title"><h2>Merek populer</h2></div>
        <div class="chip-row">
            @foreach ($brands as $b)<a class="chip" href="{{ route('catalog', ['merek' => $b->id]) }}">{{ $b->name }}</a>@endforeach
        </div>
    </div>
</section>
@endif

@if ($featured->isNotEmpty())
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-title"><h2>Pilihan unggulan</h2><span class="small muted">Iklan berbayar diberi label “Unggulan”</span></div>
        <div class="grid grid-cards cols-4">@foreach ($featured as $l)<x-listing-card :listing="$l" />@endforeach</div>
    </div>
</section>
@endif

<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-title"><h2>Iklan terbaru</h2><a href="{{ route('catalog') }}">Lihat semua</a></div>
        @if ($latest->isEmpty())
            <x-empty icon="moto" title="Belum ada iklan tayang">Jadilah yang pertama memasang iklan.</x-empty>
        @else
            <div class="grid grid-cards cols-4">@foreach ($latest as $l)<x-listing-card :listing="$l" />@endforeach</div>
        @endif
    </div>
</section>

@if ($dealers->isNotEmpty())
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-title"><h2>Dealer terverifikasi</h2><a href="{{ route('dealers.index') }}">Semua dealer</a></div>
        <div class="grid grid-2" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">
            @foreach ($dealers as $d)
                <a class="card card-body seller-card" href="{{ route('dealers.show', $d) }}" style="color:inherit;text-decoration:none">
                    <span class="avatar">{{ mb_substr($d->name, 0, 1) }}</span>
                    <span><strong>{{ $d->name }}</strong> <span class="badge badge-success"><x-icon name="verified" />Terverifikasi</span><br>
                    <span class="small muted">{{ $d->city?->name }} · {{ $d->listings_count }} iklan</span></span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="section" style="padding-top:0">
    <div class="container grid grid-2">
        <div class="card card-body">
            <h3><x-icon name="shield" class="icon icon-sm" /> Tips membeli kendaraan seken</h3>
            <ul class="muted" style="padding-left:1.1rem">
                <li>Periksa fisik unit, nomor rangka & mesin, serta kesesuaiannya dengan STNK/BPKB.</li>
                <li>Lakukan test ride/test drive dan cek riwayat servis.</li>
                <li>Jangan transfer uang muka sebelum bertemu penjual dan memeriksa dokumen.</li>
            </ul>
            <a href="{{ route('pages.guide') }}">Baca panduan lengkap</a>
        </div>
        <div class="card card-body">
            <h3><x-icon name="store" class="icon icon-sm" /> Punya dealer atau mau jual kendaraan?</h3>
            <p class="muted">Pasang iklan gratis sebagai penjual perorangan, atau kelola stok, leads, dan penjualan dealer Anda dalam satu dashboard.</p>
            <div class="row"><a class="btn btn-primary" href="{{ route('sell.create') }}">Pasang iklan</a><a class="btn btn-outline" href="{{ route('dealer.onboarding') }}">Daftar dealer</a></div>
        </div>
    </div>
</section>
@endsection
