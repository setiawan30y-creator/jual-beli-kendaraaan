@php
    $mode = auth()->user()?->theme_mode;
    $nav = [
        ['home', 'Beranda', 'home', request()->routeIs('home')],
        ['catalog', 'Cari', 'search', request()->routeIs('catalog*')],
        ['account.favorites', 'Favorit', 'heart', request()->routeIs('account.favorites')],
        ['account.leads', 'Pesan', 'chat', request()->routeIs('account.leads')],
        ['account.index', 'Akun', 'user', request()->routeIs('account.index', 'account.listings*', 'login', 'register')],
    ];
@endphp
<!DOCTYPE html>
<html lang="id" @if (in_array($mode, ['light', 'dark'], true)) data-theme="{{ $mode }}" @endif>
<head>
    @include('partials.head')
    <x-theme-style :theme="$theme ?? null" />
    @stack('head')
</head>
<body class="has-bottom-nav">
<a class="skip-link" href="#konten">Lompat ke konten</a>
<div class="offline-bar" role="status">Anda sedang offline — data mungkin tidak terbaru.</div>

<header class="app-header">
    <div class="container">
        <a class="brand" href="{{ route('home') }}" aria-label="{{ $brandName }} beranda">
            <span class="brand-mark"><x-icon name="moto" /></span>{{ $brandName }}
        </a>
        <nav class="desktop-nav" aria-label="Navigasi utama">
            <a href="{{ route('catalog.motor') }}" @if(request()->routeIs('catalog.motor*')) aria-current="page" @endif>Motor</a>
            <a href="{{ route('catalog.car') }}" @if(request()->routeIs('catalog.car')) aria-current="page" @endif>Mobil</a>
            <a href="{{ route('dealers.index') }}" @if(request()->routeIs('dealers.*')) aria-current="page" @endif>Dealer</a>
            <a href="{{ route('pages.credit') }}" @if(request()->routeIs('pages.credit')) aria-current="page" @endif>Simulasi Kredit</a>
            <a href="{{ route('pages.guide') }}" @if(request()->routeIs('pages.guide')) aria-current="page" @endif>Panduan</a>
        </nav>
        <div class="header-actions">
            <button type="button" class="btn btn-outline btn-sm hide-mobile" data-install-trigger hidden><x-icon name="download" class="icon icon-sm" />Instal Aplikasi</button>
            <a class="btn btn-primary btn-sm hide-mobile" href="{{ route('sell.create') }}"><x-icon name="add" class="icon icon-sm" />Jual</a>
            @auth
                <a class="icon-btn" href="{{ route('account.index') }}" aria-label="Akun saya"><x-icon name="user" /></a>
            @else
                <a class="btn btn-text btn-sm" href="{{ route('login') }}">Masuk</a>
                <a class="btn btn-outline btn-sm hide-mobile" href="{{ route('register') }}">Daftar</a>
            @endauth
        </div>
    </div>
</header>

<main id="konten">
    @if ($errors->any() || session('status'))
        <div class="container mt-2"><x-flash /></div>
    @endif
    @yield('content')
</main>

<footer class="app-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a class="brand" href="{{ route('home') }}"><span class="brand-mark"><x-icon name="moto" /></span>{{ $brandName }}</a>
                <p class="muted mt-1">{{ $brandTagline }}. Marketplace dan sistem dealer untuk motor & mobil baru dan seken.</p>
                <button type="button" class="btn btn-outline btn-sm" data-install-trigger hidden><x-icon name="download" class="icon icon-sm" />Instal Aplikasi</button>
            </div>
            <div>
                <h4>Jelajahi</h4>
                <ul>
                    <li><a href="{{ route('catalog.motor.new') }}">Motor baru</a></li>
                    <li><a href="{{ route('catalog.motor.used') }}">Motor seken</a></li>
                    <li><a href="{{ route('catalog.car') }}">Mobil</a></li>
                    <li><a href="{{ route('dealers.index') }}">Direktori dealer</a></li>
                </ul>
            </div>
            <div>
                <h4>Layanan</h4>
                <ul>
                    <li><a href="{{ route('sell.create') }}">Pasang iklan</a></li>
                    <li><a href="{{ route('pages.trade-in') }}">Tukar tambah</a></li>
                    <li><a href="{{ route('pages.credit') }}">Simulasi kredit</a></li>
                    <li><a href="{{ route('dealer.onboarding') }}">Daftar sebagai dealer</a></li>
                </ul>
            </div>
            <div>
                <h4>Bantuan</h4>
                <ul>
                    <li><a href="{{ route('pages.help') }}">Pusat bantuan</a></li>
                    <li><a href="{{ route('pages.report') }}">Laporkan iklan</a></li>
                    <li><a href="{{ route('pages.privacy') }}">Kebijakan privasi</a></li>
                    <li><a href="{{ route('pages.terms') }}">Syarat & ketentuan</a></li>
                    <li><a href="{{ route('pages.contact') }}">Kontak</a></li>
                </ul>
            </div>
        </div>
        <hr class="divider">
        <div class="row-between small muted">
            <span>&copy; {{ date('Y') }} {{ $brandName }}. Hati-hati penipuan: jangan transfer dana sebelum memeriksa unit & dokumen.</span>
            <span class="signature">{{ config('marketplace.developer_signature') }}</span>
        </div>
    </div>
</footer>

<nav class="bottom-nav" aria-label="Navigasi bawah">
    @foreach ($nav as [$route, $label, $icon, $active])
        <a href="{{ route($route) }}" @if($active) aria-current="page" @endif>
            <span class="pill"><x-icon :name="$active && $icon === 'heart' ? 'heart-fill' : $icon" /></span>{{ $label }}
        </a>
    @endforeach
</nav>

@include('partials.install-dialog')
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
