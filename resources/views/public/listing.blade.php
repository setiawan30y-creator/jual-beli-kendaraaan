@php
    $unit = $listing->unit; $model = $unit->vehicleModel; $F = \App\Support\Format::class;
    $isUsed = $listing->condition === 'used';
@endphp
@extends('layouts.app', [
    'pageTitle' => $listing->title.' — '.$F::rupiah($listing->price),
    'metaDescription' => \Illuminate\Support\Str::limit(($isUsed ? 'Seken' : 'Baru').' · '.$unit->year.' · '.($listing->city?->name ?? '').'. '.$listing->description, 155),
    'canonical' => route('listings.show', $listing),
    'ogType' => 'product',
    'ogImage' => $listing->coverUrl(),
    'noindex' => $isPreview ? true : null,
    'theme' => $listing->tenant?->theme(),
])

@push('head')
@unless ($isPreview)
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org', '@type' => 'Product', 'name' => $listing->title,
    'image' => $photos->map(fn ($p) => $p->url())->values(), 'description' => \Illuminate\Support\Str::limit((string) $listing->description, 300),
    'brand' => ['@type' => 'Brand', 'name' => $model->brand->name],
    'offers' => ['@type' => 'Offer', 'priceCurrency' => 'IDR', 'price' => (int) $listing->price,
        'itemCondition' => $isUsed ? 'https://schema.org/UsedCondition' : 'https://schema.org/NewCondition',
        'availability' => $listing->status === 'published' ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
        'url' => route('listings.show', $listing)],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endunless
@endpush

@section('content')
<div class="container section">
    @if ($isPreview)
        <div class="notice notice-warning mb-2"><x-icon name="info" /><div>Pratinjau: iklan ini berstatus <strong>{{ \App\Models\Listing::STATUSES[$listing->status] ?? $listing->status }}</strong> dan belum tampil untuk publik.</div></div>
    @endif
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('home') }}">Beranda</a><span>›</span>
        <a href="{{ route('catalog', ['kategori' => $listing->category]) }}">{{ ucfirst($listing->category) }}</a><span>›</span>
        <a href="{{ route('catalog', ['merek' => $model->brand_id]) }}">{{ $model->brand->name }}</a><span>›</span><span>{{ $model->name }}</span>
    </nav>

    <div class="detail">
        <div class="stack">
            <div>
                <div class="gallery" data-gallery>
                    <div class="gallery-track" tabindex="0" aria-label="Galeri foto">
                        @forelse ($photos as $i => $p)
                            <figure><img src="{{ $p->url() }}" alt="{{ $listing->title }} — {{ \App\Models\UnitMedia::CATEGORIES[$p->category]['label'] }}" @if($i) loading="lazy" @endif width="800" height="600"></figure>
                        @empty
                            <figure class="ph"><x-icon :name="$listing->category === 'mobil' ? 'car' : 'moto'" /></figure>
                        @endforelse
                    </div>
                    @if ($photos->count() > 1)<span class="gallery-count">1/{{ $photos->count() }}</span>@endif
                </div>
                @if ($photos->count() > 1)
                    <div class="gallery-thumbs">
                        @foreach ($photos as $i => $p)<button type="button" data-thumb aria-current="{{ $i ? 'false' : 'true' }}" aria-label="Foto {{ $i + 1 }}"><img src="{{ $p->url() }}" alt="" loading="lazy"></button>@endforeach
                    </div>
                @endif
            </div>

            <div class="card card-body">
                <h2 class="card-title">Spesifikasi</h2>
                <dl class="spec-list">
                    <div><dt>Merek / model</dt><dd>{{ $model->fullName() }}</dd></div>
                    @if ($unit->variant)<div><dt>Varian</dt><dd>{{ $unit->variant->name }}</dd></div>@endif
                    <div><dt>Tahun</dt><dd>{{ $unit->year }}</dd></div>
                    <div><dt>Kondisi</dt><dd>{{ $isUsed ? 'Seken' : 'Baru' }}</dd></div>
                    @if ($isUsed)<div><dt>Kilometer</dt><dd>{{ $F::km($unit->odometer_km) }}</dd></div>@endif
                    @if ($unit->color)<div><dt>Warna</dt><dd>{{ $unit->color }}</dd></div>@endif
                    @if ($model->engine_cc)<div><dt>Mesin</dt><dd>{{ number_format($model->engine_cc, 0, ',', '.') }} cc</dd></div>@endif
                    @if ($model->transmission)<div><dt>Transmisi</dt><dd>{{ ucfirst($model->transmission) }}</dd></div>@endif
                    @if ($model->body_type)<div><dt>Tipe</dt><dd>{{ ucfirst($model->body_type) }}</dd></div>@endif
                    @if ($unit->registration_status)<div><dt>Dokumen (menurut penjual)</dt><dd>{{ ucfirst(str_replace('_', ' ', $unit->registration_status)) }}</dd></div>@endif
                </dl>
            </div>

            <div class="card card-body">
                <h2 class="card-title">Deskripsi penjual</h2>
                <div style="white-space:pre-line">{{ $listing->description ?: 'Penjual belum menambahkan deskripsi.' }}</div>
                <p class="small muted mt-2 mb-0">Informasi di atas berasal dari penjual. Nomor rangka/mesin dan dokumen kepemilikan tidak ditampilkan publik demi keamanan; minta penjual menunjukkannya saat pemeriksaan langsung.</p>
            </div>

            <div class="notice notice-info"><x-icon name="shield" /><div><strong>Transaksi aman:</strong> periksa unit & dokumen secara langsung, cocokkan nomor rangka/mesin dengan STNK/BPKB, dan hindari transfer tanda jadi sebelum bertemu penjual. <a href="{{ route('pages.guide') }}">Panduan pembeli</a></div></div>
        </div>

        <aside class="detail-aside stack">
            <div class="card card-body stack">
                <div class="row">
                    <span class="badge {{ $isUsed ? 'badge-used' : 'badge-new' }}">{{ $isUsed ? 'Seken' : 'Baru' }}</span>
                    @if ($listing->is_featured)<span class="badge badge-featured">Unggulan</span>@endif
                    @if ($listing->status !== 'published')<x-status-badge :status="$listing->status" :label="\App\Models\Listing::STATUSES[$listing->status] ?? null" />@endif
                </div>
                <h1 style="font-size:1.35rem" class="mb-0">{{ $listing->title }}</h1>
                <div class="price-xl">{{ $F::rupiah($listing->price) }}</div>
                <div class="muted small">{{ $listing->negotiable ? 'Harga dapat dinegosiasikan' : 'Harga pas' }} · <x-icon name="pin" class="icon icon-sm" style="vertical-align:-3px" /> {{ $listing->city?->name ?? 'Lokasi tidak diisi' }}</div>
                <div class="row">
                    @auth
                        <form method="post" action="{{ route('listings.favorite', $listing) }}" data-fav-form>@csrf
                            <button class="fav-btn" aria-pressed="{{ $isFavorite ? 'true' : 'false' }}" aria-label="{{ $isFavorite ? 'Hapus dari favorit' : 'Simpan ke favorit' }}"><x-icon :name="$isFavorite ? 'heart-fill' : 'heart'" /></button>
                        </form>
                    @endauth
                    <button type="button" class="btn btn-outline btn-sm" data-share><x-icon name="share" class="icon icon-sm" />Bagikan</button>
                </div>
            </div>

            @if ($listing->status === 'published' && ! $isPreview)
            <div class="card card-body" id="hubungi">
                <h2 class="card-title">Hubungi penjual</h2>
                <form method="post" action="{{ route('listings.lead', $listing) }}" class="stack">
                    @csrf
                    <x-field name="buyer_name" label="Nama" :value="auth()->user()?->name" required autocomplete="name" placeholder="Nama lengkap" />
                    <x-field name="buyer_phone" label="Nomor WhatsApp/telepon" type="tel" :value="auth()->user()?->phone" required autocomplete="tel" placeholder="08xxxxxxxxxx" />
                    <x-field name="message" label="Pesan (opsional)" type="textarea" value="Halo, apakah unit ini masih tersedia?" rows="3" />
                    <label class="check"><input type="checkbox" name="contact_consent" value="1" required @checked(old('contact_consent'))> <span>Saya setuju data kontak saya diteruskan ke penjual untuk keperluan iklan ini.</span></label>
                    <div class="sticky-cta">
                        <button class="btn btn-primary" style="flex:1" name="source" value="form"><x-icon name="chat" />Kirim minat</button>
                        @if ($listing->whatsappNumber())<button class="btn btn-success" style="flex:1" name="source" value="whatsapp"><x-icon name="phone" />WhatsApp</button>@endif
                    </div>
                </form>
            </div>

            <div class="card card-body">
                <h2 class="card-title">Jadwalkan kunjungan / test ride</h2>
                @auth
                    <form method="post" action="{{ route('listings.book', $listing) }}" class="stack">
                        @csrf
                        <x-field name="booking_type" label="Jenis" type="select">
                            @foreach (\App\Models\Booking::TYPES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                        </x-field>
                        <x-field name="starts_at" label="Tanggal & jam" type="datetime-local" required :min="now()->addHour()->format('Y-m-d\TH:i')" />
                        <x-field name="notes" label="Catatan (opsional)" type="textarea" rows="2" />
                        <button class="btn btn-outline">Ajukan jadwal</button>
                    </form>
                @else
                    <p class="muted mb-0"><a href="{{ route('login') }}">Masuk</a> untuk mengajukan jadwal kunjungan.</p>
                @endauth
            </div>
            @endif

            <div class="card card-body">
                <h2 class="card-title">Penjual</h2>
                @if ($listing->tenant)
                    <a class="seller-card" href="{{ route('dealers.show', $listing->tenant) }}" style="color:inherit">
                        <span class="avatar">{{ mb_substr($listing->tenant->name, 0, 1) }}</span>
                        <span><strong>{{ $listing->tenant->name }}</strong><br>
                            @if ($listing->tenant->isVerified())<span class="badge badge-success"><x-icon name="verified" />Dealer terverifikasi</span>@else<span class="badge">Dealer belum terverifikasi</span>@endif
                            <span class="small muted d-block">{{ $listing->tenant->city?->name }}</span></span>
                    </a>
                @else
                    <div class="seller-card"><span class="avatar">{{ mb_substr($listing->seller->name, 0, 1) }}</span><span><strong>{{ $listing->seller->name }}</strong><br><span class="small muted">Penjual perorangan · bergabung {{ $listing->seller->created_at->translatedFormat('M Y') }}</span></span></div>
                @endif
            </div>

            <details class="card card-body">
                <summary style="cursor:pointer;font-weight:600"><x-icon name="flag" class="icon icon-sm" style="vertical-align:-3px" /> Laporkan iklan ini</summary>
                <form method="post" action="{{ route('listings.report', $listing) }}" class="stack mt-2">
                    @csrf
                    <x-field name="reason" label="Alasan" type="select" required>
                        @foreach (\App\Models\ListingReport::REASONS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </x-field>
                    <x-field name="description" label="Keterangan" type="textarea" rows="3" />
                    <button class="btn btn-outline btn-sm">Kirim laporan</button>
                </form>
            </details>
        </aside>
    </div>

    @if ($similar->isNotEmpty())
        <section class="section">
            <div class="section-title"><h2>Kendaraan serupa</h2></div>
            <div class="grid grid-cards cols-4">@foreach ($similar as $s)<x-listing-card :listing="$s" />@endforeach</div>
        </section>
    @endif
</div>
@endsection
