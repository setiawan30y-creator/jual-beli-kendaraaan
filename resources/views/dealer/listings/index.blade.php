@extends('layouts.dealer')
@section('heading', 'Iklan')
@section('content')
@php $F = \App\Support\Format::class; @endphp
<div class="row-between mb-2"><h1 class="mb-0">Iklan</h1>
    <span class="small muted">Kuota iklan aktif: <strong>{{ $usage['used'] }} / {{ $usage['limit'] ?? '∞' }}</strong> · <a href="{{ route('dealer.billing') }}">Paket</a></span></div>
<div class="chip-row mb-2">
    <a class="chip {{ ! $status ? 'is-active' : '' }}" href="{{ route('dealer.listings.index') }}">Semua</a>
    @foreach (\App\Models\Listing::STATUSES as $k => $v)<a class="chip {{ $status === $k ? 'is-active' : '' }}" href="{{ route('dealer.listings.index', ['status' => $k]) }}">{{ $v }}</a>@endforeach
</div>
@if ($listings->isEmpty())
    <div class="card"><x-empty icon="tag" title="Belum ada iklan">Buat iklan dari halaman detail unit di inventaris.</x-empty></div>
@else
<div class="card"><div class="table-wrap"><table class="table stacked">
    <thead><tr><th>Iklan</th><th>Status</th><th class="num">Harga</th><th class="num">Dilihat</th><th class="num">Leads</th><th>Aksi</th></tr></thead>
    <tbody>
    @foreach ($listings as $l)
        <tr>
            <td data-label="Iklan"><a href="{{ route('dealer.units.show', $l->unit_id) }}"><strong>{{ $l->title }}</strong></a>
                @if ($l->status === 'rejected')<br><span class="small" style="color:var(--danger)">{{ $l->rejection_reason }}</span>@endif
                @if ($l->expires_at && $l->status === 'published')<br><span class="small muted">Tayang s.d. {{ $l->expires_at->format('d/m/Y') }}</span>@endif</td>
            <td data-label="Status"><x-status-badge :status="$l->status" :label="\App\Models\Listing::STATUSES[$l->status]" /></td>
            <td data-label="Harga" class="num">{{ $F::rupiah($l->price) }}</td>
            <td data-label="Dilihat" class="num">{{ $l->views_count }}</td>
            <td data-label="Leads" class="num">{{ $l->leads_count }}</td>
            <td data-label="Aksi">@if ($canManage)<div class="row" style="gap:4px;justify-content:flex-end">
                @if (in_array($l->status, ['draft', 'rejected', 'expired']))<form method="post" action="{{ route('dealer.listings.submit', $l) }}">@csrf<button class="btn btn-primary btn-sm">Ajukan</button></form>@endif
                @if (in_array($l->status, ['published', 'reserved']))<form method="post" action="{{ route('dealer.listings.sold', $l) }}" data-confirm="Tandai terjual?">@csrf<button class="btn btn-outline btn-sm">Terjual</button></form>@endif
                @if (! in_array($l->status, ['archived', 'pending_review']))<form method="post" action="{{ route('dealer.listings.archive', $l) }}" data-confirm="Arsipkan iklan?">@csrf<button class="btn btn-text btn-sm">Arsip</button></form>@endif
                <a class="btn btn-text btn-sm" href="{{ route('listings.show', $l) }}" target="_blank">Lihat</a>
            </div>@endif</td>
        </tr>
    @endforeach
    </tbody></table></div></div>
{{ $listings->links() }}
@endif
@endsection
