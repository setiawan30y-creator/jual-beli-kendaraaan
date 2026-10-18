{{-- Shell dashboard dealer & admin: sidebar kiri (drawer di HP) + header (Blueprint UI §13) --}}
@php $mode = auth()->user()?->theme_mode; @endphp
<!DOCTYPE html>
<html lang="id" @if (in_array($mode, ['light', 'dark'], true)) data-theme="{{ $mode }}" @endif>
<head>
    @include('partials.head', ['noindex' => true])
    <x-theme-style :theme="$panelTheme ?? null" />
</head>
<body>
<a class="skip-link" href="#konten">Lompat ke konten</a>
<div class="offline-bar" role="status">Anda sedang offline — perubahan tidak dapat disimpan.</div>
<div class="panel">
    <div class="sheet-backdrop" id="sidebar-backdrop"></div>
    <aside class="sidebar" id="sidebar" aria-label="Menu panel">
        <div class="row-between">
            <a class="brand" href="{{ route('home') }}"><span class="brand-mark"><x-icon name="moto" /></span>{{ $brandName }}</a>
            <button class="icon-btn sidebar-close" type="button" data-close aria-label="Tutup menu"><x-icon name="close" /></button>
        </div>
        @yield('sidebar-top')
        <nav>
            @foreach ($panelNav as $item)
                @if (isset($item['group']))
                    <div class="nav-group">{{ $item['group'] }}</div>
                @else
                    <a href="{{ $item['url'] }}" @if($item['active']) aria-current="page" @endif><x-icon :name="$item['icon']" />{{ $item['label'] }}</a>
                @endif
            @endforeach
            <div class="nav-group">Akun</div>
            <a href="{{ route('account.index') }}"><x-icon name="user" />Akun saya</a>
            <a href="{{ route('home') }}"><x-icon name="home" />Lihat marketplace</a>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-text btn-block" style="justify-content:flex-start;gap:12px;padding:10px 12px"><x-icon name="logout" />Keluar</button></form>
        </nav>
        <p class="signature mt-3" style="padding:0 12px">{{ config('marketplace.developer_signature') }}</p>
    </aside>
    <div class="panel-main">
        <header class="panel-header">
            <button class="icon-btn menu-trigger" type="button" data-open-menu aria-controls="sidebar" aria-label="Buka menu"><x-icon name="menu" /></button>
            <strong style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">@yield('heading')</strong>
            @yield('header-widgets')
        </header>
        <main id="konten" class="panel-content">
            <x-flash />
            @yield('content')
        </main>
    </div>
</div>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
