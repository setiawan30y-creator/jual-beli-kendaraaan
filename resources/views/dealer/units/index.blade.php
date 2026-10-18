@extends('layouts.dealer')
@section('heading', 'Inventaris unit')
@section('content')
@php $F = \App\Support\Format::class; $ctx = app(\App\Support\TenantContext::class); @endphp
<div class="row-between mb-2">
    <h1 class="mb-0">Inventaris unit</h1>
    @if ($ctx->can('units.manage'))<a class="btn btn-primary" href="{{ route('dealer.units.create') }}"><x-icon name="add" />Tambah unit</a>@endif
</div>
<div class="chip-row mb-2">
    <a class="chip {{ ! $status ? 'is-active' : '' }}" href="{{ route('dealer.units.index') }}">Semua</a>
    @foreach (\App\Models\VehicleUnit::STATUSES as $k => $v)<a class="chip {{ $status === $k ? 'is-active' : '' }}" href="{{ route('dealer.units.index', ['status' => $k]) }}">{{ $v }}</a>@endforeach
</div>
<form class="row mb-2" method="get"><input type="hidden" name="status" value="{{ $status }}"><input class="input" style="flex:1" name="q" value="{{ $q }}" placeholder="Cari kode stok atau model" aria-label="Cari unit"><button class="btn btn-outline"><x-icon name="search" />Cari</button></form>
@if ($units->isEmpty())
    <div class="card"><x-empty icon="moto" title="Belum ada unit">Tambahkan unit pertama untuk mulai beriklan.</x-empty></div>
@else
<div class="card"><div class="table-wrap"><table class="table stacked">
    <thead><tr><th>Unit</th><th>Kode</th><th>Kondisi</th><th>Status</th><th>Iklan</th><th>Foto</th><th class="num">Harga jual</th></tr></thead>
    <tbody>
    @foreach ($units as $u)
        <tr>
            <td data-label="Unit"><a href="{{ route('dealer.units.show', $u) }}"><strong>{{ $u->display_name }}</strong></a><br><span class="small muted">{{ $u->color }} · {{ $u->branch?->name }}</span>
                @if ($u->ownership_type === 'consignment')<span class="badge badge-warning">Titipan</span>@endif</td>
            <td data-label="Kode">{{ $u->stock_code ?? '—' }}</td>
            <td data-label="Kondisi">{{ $u->condition === 'new' ? 'Baru' : 'Seken' }}</td>
            <td data-label="Status"><x-status-badge :status="$u->status" :label="\App\Models\VehicleUnit::STATUSES[$u->status]" /></td>
            <td data-label="Iklan">@if ($u->listing)<x-status-badge :status="$u->listing->status" :label="\App\Models\Listing::STATUSES[$u->listing->status]" />@else<span class="muted">—</span>@endif</td>
            <td data-label="Foto">{{ $u->media->filter->isPublic()->count() }} publik</td>
            <td data-label="Harga jual" class="num">{{ $F::rupiah($u->asking_price) }}</td>
        </tr>
    @endforeach
    </tbody></table></div></div>
{{ $units->links() }}
@endif
@endsection
