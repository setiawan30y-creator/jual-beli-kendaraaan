@extends('layouts.app', ['pageTitle' => $title, 'canonical' => url()->current(), 'noindex' => request()->query() ? true : null])

@section('content')
<div class="container section">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Beranda</a><span>›</span><span>{{ $title }}</span></nav>
    <div class="row-between mb-2">
        <div>
            <h1 class="mb-0">{{ $title }}</h1>
            <span class="muted small" aria-live="polite">{{ number_format($listings->total(), 0, ',', '.') }} kendaraan ditemukan</span>
        </div>
    </div>

    <form method="get" action="{{ url()->current() }}" class="row mb-2" role="search">
        @foreach (array_filter(\Illuminate\Support\Arr::except($filters, ['q', 'urut']), fn ($v) => $v !== null) as $k => $v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach
        <input class="input" style="flex:1;min-width:180px" name="q" value="{{ $filters['q'] }}" placeholder="Cari merek, model, judul" aria-label="Kata kunci">
        <select class="select" name="urut" style="width:auto" aria-label="Urutkan" onchange="this.form.submit()">
            @foreach ($sorts as $k => $label)<option value="{{ $k }}" @selected($filters['urut'] === $k)>{{ $label }}</option>@endforeach
        </select>
        <button class="btn btn-primary" aria-label="Cari"><x-icon name="search" /></button>
        <button type="button" class="btn btn-outline filter-trigger" data-open-filter aria-controls="filter-sheet"><x-icon name="tune" />Filter @if($activeFilterCount)<span class="badge badge-info">{{ $activeFilterCount }}</span>@endif</button>
    </form>

    <div class="catalog">
        <aside class="filter-panel card card-body" aria-label="Filter">
            <h2 class="card-title">Filter @if($activeFilterCount)<span class="badge badge-info">{{ $activeFilterCount }} aktif</span>@endif</h2>
            @include('public.partials-filter', ['p' => 'd_'])
        </aside>
        <div>
            @if ($listings->isEmpty())
                <div class="card"><x-empty title="Tidak ada kendaraan yang cocok">Coba kurangi filter atau ubah kata kunci. <a href="{{ url()->current() }}">Reset filter</a></x-empty></div>
            @else
                <div class="grid grid-cards">
                    @php $favIds = auth()->check() ? auth()->user()->favorites()->pluck('listings.id')->all() : []; @endphp
                    @foreach ($listings as $l)<x-listing-card :listing="$l" :favorite="in_array($l->id, $favIds)" />@endforeach
                </div>
                {{ $listings->links() }}
            @endif
        </div>
    </div>
</div>

<div class="sheet-backdrop" id="filter-backdrop"></div>
<div class="sheet" id="filter-sheet" role="dialog" aria-modal="true" aria-label="Filter" aria-hidden="true">
    <div class="row-between mb-2"><h2 class="mb-0">Filter</h2><button type="button" class="icon-btn" data-close aria-label="Tutup filter"><x-icon name="close" /></button></div>
    @include('public.partials-filter', ['p' => 'm_'])
</div>
@endsection
