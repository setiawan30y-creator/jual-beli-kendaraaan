@extends('layouts.dealer')
@section('heading', 'Nasabah & pemasok')
@section('content')
@php $ctx = app(\App\Support\TenantContext::class); @endphp
<div class="row-between mb-2">
    <h1 class="mb-0">Nasabah & pemasok</h1>
    @if ($ctx->can('parties.manage'))
        <div class="row">
            <a class="btn btn-primary" href="{{ route('dealer.parties.create', ['peran' => 'customer']) }}"><x-icon name="add" />Tambah nasabah</a>
            <a class="btn btn-outline" href="{{ route('dealer.parties.create', ['peran' => 'vehicle_source']) }}">Tambah sumber unit/pemasok</a>
        </div>
    @endif
</div>
<div class="chip-row mb-2">
    <a class="chip {{ ! $role ? 'is-active' : '' }}" href="{{ route('dealer.parties.index', array_filter(['status' => $status, 'q' => $q])) }}">Semua peran</a>
    @foreach (\App\Models\Party::ROLES as $k => $v)
        <a class="chip {{ $role === $k ? 'is-active' : '' }}" href="{{ route('dealer.parties.index', array_filter(['peran' => $k, 'status' => $status, 'q' => $q])) }}">{{ $v }}</a>
    @endforeach
</div>
<form class="row mb-2" method="get">
    <input type="hidden" name="peran" value="{{ $role }}">
    <input class="input" style="flex:1;min-width:200px" name="q" value="{{ $q }}" placeholder="Cari nama, telepon, atau nomor identitas lengkap" aria-label="Cari">
    <select class="select" name="status" style="width:auto" aria-label="Status identitas">
        <option value="">Semua status identitas</option>
        @foreach (\App\Models\Party::IDENTITY_STATUSES as $k => $v)<option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>@endforeach
    </select>
    <button class="btn btn-outline"><x-icon name="search" />Cari</button>
</form>
@if ($parties->isEmpty())
    <div class="card"><x-empty icon="people" title="Belum ada data">Tambahkan nasabah baru, atau ubah lead menjadi nasabah dari halaman detail lead.</x-empty></div>
@else
<div class="card"><div class="table-wrap"><table class="table stacked">
    <thead><tr><th>Nama</th><th>Peran</th><th>Identitas</th><th>Status identitas</th><th>Telepon</th><th>Kota</th></tr></thead>
    <tbody>
    @foreach ($parties as $p)
        <tr>
            <td data-label="Nama"><a href="{{ route('dealer.parties.show', $p) }}"><strong>{{ $p->name }}</strong></a><br><span class="small muted">{{ ucfirst($p->type) }}</span></td>
            <td data-label="Peran">@foreach ($p->roleLabels() as $r)<span class="badge" style="margin:1px">{{ $r }}</span>@endforeach</td>
            <td data-label="Identitas">{{ $p->idTypeLabel() ?? '—' }}<br><span class="small muted">{{ $p->maskedIdNumber() }}</span></td>
            <td data-label="Status"><x-status-badge :status="$p->identity_status === 'belum_lengkap' ? 'draft' : ($p->identity_status === 'ditolak' ? 'rejected' : $p->identity_status)" :label="\App\Models\Party::IDENTITY_STATUSES[$p->identity_status]" /></td>
            <td data-label="Telepon">{{ $p->phone ?? '—' }}</td>
            <td data-label="Kota">{{ $p->city?->name ?? '—' }}</td>
        </tr>
    @endforeach
    </tbody></table></div></div>
{{ $parties->links() }}
@endif
@endsection
