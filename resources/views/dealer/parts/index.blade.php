@extends('layouts.dealer')
@section('heading', 'Stok sparepart')
@section('content')
@php $F = \App\Support\Format::class; $Q = fn ($v) => \App\Models\SparePart::qty($v); @endphp
<div class="row-between mb-2">
    <h1 class="mb-0">Stok sparepart</h1>
    @if ($canManage)<a class="btn btn-primary" href="#tambah" onclick="document.getElementById('tambah').open = true"><x-icon name="add" />Tambah sparepart</a>@endif
</div>
<div class="stat-grid mb-2" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
    <div class="card stat"><div class="label">Nilai persediaan</div><div class="value">{{ $F::rupiah($totalValue) }}</div></div>
    <a class="card stat" href="{{ route('dealer.parts.index', ['menipis' => 1]) }}" style="color:inherit"><div class="label">Stok menipis</div><div class="value" style="{{ $lowCount ? 'color:var(--warning)' : '' }}">{{ $lowCount }}</div></a>
</div>
<div class="notice notice-info mb-2"><x-icon name="info" /><div>Alur: <strong>Terima barang</strong> dari pemasok menambah stok → di halaman detail unit pilih <strong>Pakai sparepart dari stok</strong> → stok berkurang dan nilainya (harga rata-rata) otomatis masuk <strong>HPP unit</strong>. Membalik biaya sparepart di unit akan mengembalikan barang ke stok.</div></div>

@if ($canManage)
<details class="card card-body mb-2" id="tambah" @if($errors->any()) open @endif>
    <summary style="cursor:pointer;font-weight:700">Tambah sparepart baru</summary>
    <form method="post" action="{{ route('dealer.parts.store') }}" class="form-grid cols-2 mt-2">
        @csrf
        <x-field name="sku" label="Kode SKU" required placeholder="Contoh: OLI-MPX-08" />
        <x-field name="name" label="Nama sparepart" required placeholder="Contoh: Oli mesin 0.8L" />
        <x-field name="category" label="Kategori" type="select"><option value="">—</option>@foreach (\App\Models\SparePart::CATEGORIES as $k => $v)<option value="{{ $k }}" @selected(old('category') === $k)>{{ $v }}</option>@endforeach</x-field>
        <x-field name="uom" label="Satuan" value="pcs" required placeholder="pcs, liter, set" />
        <x-field name="min_stock" label="Stok minimum (peringatan)" type="number" min="0" step="0.01" value="0" />
        <x-field name="location" label="Lokasi rak/gudang" placeholder="Contoh: Rak A-2" />
        <x-field name="compatible_with" label="Cocok untuk" class="span-2" placeholder="Contoh: Vario 160, PCX 160" />
        <div class="span-2"><button class="btn btn-primary">Simpan sparepart</button></div>
    </form>
</details>
@endif

<form class="row mb-2" method="get">
    <input class="input" style="flex:1;min-width:180px" name="q" value="{{ $q }}" placeholder="Cari nama atau SKU" aria-label="Cari sparepart">
    <select class="select" name="kategori" style="width:auto" aria-label="Kategori"><option value="">Semua kategori</option>@foreach (\App\Models\SparePart::CATEGORIES as $k => $v)<option value="{{ $k }}" @selected($cat === $k)>{{ $v }}</option>@endforeach</select>
    <label class="check small"><input type="checkbox" name="menipis" value="1" @checked($low)> <span>Hanya stok menipis</span></label>
    <button class="btn btn-outline"><x-icon name="search" />Filter</button>
</form>

@if ($parts->isEmpty())
    <div class="card"><x-empty icon="settings" title="Belum ada sparepart">Tambahkan sparepart, lalu catat penerimaan barang.</x-empty></div>
@else
<div class="card"><div class="table-wrap"><table class="table stacked">
    <thead><tr><th>Sparepart</th><th>Kategori</th><th>Lokasi</th><th class="num">Stok</th><th class="num">Harga rata-rata</th><th class="num">Nilai</th></tr></thead>
    <tbody>
    @foreach ($parts as $p)
        <tr>
            <td data-label="Sparepart"><a href="{{ route('dealer.parts.show', $p) }}"><strong>{{ $p->name }}</strong></a><br><span class="small muted">{{ $p->sku }}{{ $p->compatible_with ? ' · '.$p->compatible_with : '' }}</span>
                @if ($p->status !== 'active')<span class="badge">Nonaktif</span>@endif</td>
            <td data-label="Kategori">{{ \App\Models\SparePart::CATEGORIES[$p->category] ?? '—' }}</td>
            <td data-label="Lokasi">{{ $p->location ?? '—' }}</td>
            <td data-label="Stok" class="num">{{ $Q($p->qty_on_hand) }} {{ $p->uom }}
                @if ($p->isLow())<br><span class="badge badge-warning"><x-icon name="warning" />Menipis (min {{ $Q($p->min_stock) }})</span>@endif</td>
            <td data-label="Harga rata-rata" class="num">{{ $F::rupiah($p->avg_cost) }}</td>
            <td data-label="Nilai" class="num">{{ $F::rupiah($p->stockValue()) }}</td>
        </tr>
    @endforeach
    </tbody></table></div></div>
{{ $parts->links() }}
@endif
@endsection
