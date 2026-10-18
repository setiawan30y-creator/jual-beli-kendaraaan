@props(['listing', 'favorite' => null])
@php
    $cover = $listing->coverUrl();
    $unit = $listing->unit;
    $isFav = $favorite ?? false;
@endphp
<article class="vcard {{ $listing->status === 'sold' ? 'is-sold' : '' }}">
    <div class="vcard-media">
        @if ($cover)
            <img src="{{ $cover }}" alt="{{ $listing->title }}" loading="lazy" width="400" height="300">
        @else
            <div class="ph"><x-icon :name="$listing->category === 'mobil' ? 'car' : 'moto'" /></div>
        @endif
        <div class="vcard-badges">
            <span class="badge {{ $listing->condition === 'new' ? 'badge-new' : 'badge-used' }}">{{ $listing->condition === 'new' ? 'Baru' : 'Seken' }}</span>
            @if ($listing->is_featured)<span class="badge badge-featured" title="Iklan unggulan berbayar">Unggulan</span>@endif
            @if ($listing->status === 'reserved')<span class="badge badge-warning">Dipesan</span>@endif
        </div>
    </div>
    @auth
        <form class="vcard-fav" method="post" action="{{ route('listings.favorite', $listing) }}" data-fav-form>
            @csrf
            <button class="fav-btn" type="submit" aria-pressed="{{ $isFav ? 'true' : 'false' }}" aria-label="{{ $isFav ? 'Hapus dari favorit' : 'Simpan ke favorit' }}">
                <x-icon :name="$isFav ? 'heart-fill' : 'heart'" />
            </button>
        </form>
    @else
        <a class="vcard-fav fav-btn" href="{{ route('login') }}" aria-label="Masuk untuk menyimpan favorit"><x-icon name="heart" /></a>
    @endauth
    <div class="vcard-body">
        <div class="vcard-price">{{ \App\Support\Format::rupiah($listing->price) }}</div>
        <a class="vcard-title" href="{{ route('listings.show', $listing) }}">{{ $listing->title }}</a>
        <div class="vcard-meta">
            <span>{{ $unit?->year }}</span>
            @if ($listing->condition === 'used' && $unit?->odometer_km !== null)<span><x-icon name="speed" />{{ \App\Support\Format::km($unit->odometer_km) }}</span>@endif
            @if ($listing->city)<span><x-icon name="pin" />{{ $listing->city->name }}</span>@endif
        </div>
        @if ($listing->tenant)
            <div class="vcard-meta"><span>{{ $listing->tenant->name }}</span>
                @if ($listing->tenant->isVerified())<span class="badge badge-success" style="padding:1px 6px"><x-icon name="verified" />Terverifikasi</span>@endif
            </div>
        @else
            <div class="vcard-meta"><span>Penjual perorangan</span></div>
        @endif
    </div>
</article>
