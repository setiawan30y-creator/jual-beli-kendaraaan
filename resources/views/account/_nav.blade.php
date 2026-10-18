<nav class="tabs" aria-label="Menu akun">
    <a href="{{ route('account.index') }}" @if(request()->routeIs('account.index')) aria-current="page" @endif>Ringkasan</a>
    <a href="{{ route('account.favorites') }}" @if(request()->routeIs('account.favorites')) aria-current="page" @endif>Favorit</a>
    <a href="{{ route('account.listings') }}" @if(request()->routeIs('account.listings*')) aria-current="page" @endif>Iklan saya</a>
    <a href="{{ route('account.leads') }}" @if(request()->routeIs('account.leads')) aria-current="page" @endif>Pesan & jadwal</a>
</nav>
