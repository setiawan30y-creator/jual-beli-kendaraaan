@extends('layouts.app', ['pageTitle' => 'Direktori dealer', 'canonical' => route('dealers.index')])
@section('content')
<div class="container section">
    <h1>Direktori dealer</h1>
    <form method="get" class="row mb-2" role="search">
        <input class="input" style="flex:1" name="q" value="{{ $q }}" placeholder="Cari nama dealer" aria-label="Nama dealer">
        <button class="btn btn-primary"><x-icon name="search" />Cari</button>
    </form>
    @if ($dealers->isEmpty())
        <div class="card"><x-empty icon="store" title="Dealer tidak ditemukan">Coba kata kunci lain.</x-empty></div>
    @else
        <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
            @foreach ($dealers as $d)
                <a class="card card-body seller-card" href="{{ route('dealers.show', $d) }}" style="color:inherit;text-decoration:none">
                    <span class="avatar">{{ mb_substr($d->name, 0, 1) }}</span>
                    <span><strong>{{ $d->name }}</strong><br>
                        @if ($d->isVerified())<span class="badge badge-success"><x-icon name="verified" />Terverifikasi</span>@else<span class="badge">Belum terverifikasi</span>@endif
                        <span class="small muted">{{ $d->city?->name }} · {{ $d->listings_count }} iklan</span></span>
                </a>
            @endforeach
        </div>
        {{ $dealers->links() }}
    @endif
</div>
@endsection
