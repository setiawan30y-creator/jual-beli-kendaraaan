@extends('layouts.dealer')
@section('heading', $part->name)
@section('content')
@php $F = \App\Support\Format::class; $Q = fn ($v) => \App\Models\SparePart::qty($v); @endphp
<nav class="breadcrumb"><a href="{{ route('dealer.parts.index') }}">Stok sparepart</a><span>›</span><span>{{ $part->sku }}</span></nav>
<div class="row-between mb-2">
    <div><h1 class="mb-0">{{ $part->name }}</h1><span class="muted small">{{ $part->sku }} · {{ \App\Models\SparePart::CATEGORIES[$part->category] ?? 'Tanpa kategori' }}{{ $part->location ? ' · '.$part->location : '' }}</span></div>
</div>
<div class="stat-grid mb-2" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr))">
    <div class="card stat"><div class="label">Stok tersedia</div><div class="value" style="{{ $part->isLow() ? 'color:var(--warning)' : '' }}">{{ $Q($part->qty_on_hand) }} <span class="small muted">{{ $part->uom }}</span></div></div>
    <div class="card stat"><div class="label">Harga rata-rata</div><div class="value">{{ $F::rupiah($part->avg_cost) }}</div></div>
    <div class="card stat"><div class="label">Nilai persediaan</div><div class="value">{{ $F::rupiah($part->stockValue()) }}</div></div>
    <div class="card stat"><div class="label">Stok minimum</div><div class="value">{{ $Q($part->min_stock) }}</div></div>
</div>
<div class="detail">
    <div>
        <h2>Kartu stok</h2>
        <div class="card"><div class="table-wrap"><table class="table stacked">
            <thead><tr><th>Tanggal</th><th>Jenis</th><th>Keterangan</th><th class="num">Masuk</th><th class="num">Keluar</th><th class="num">Harga</th><th class="num">Saldo</th></tr></thead>
            <tbody>
            @forelse ($movements as $m)
                @php $in = \App\Models\InventoryMovement::TYPES[$m->type]['sign'] > 0; @endphp
                <tr>
                    <td data-label="Tanggal" class="nowrap">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                    <td data-label="Jenis"><span class="badge {{ $in ? 'badge-success' : 'badge-info' }}">{{ \App\Models\InventoryMovement::TYPES[$m->type]['label'] }}</span></td>
                    <td data-label="Keterangan" class="small">
                        @if ($m->unit)<a href="{{ route('dealer.units.show', $m->unit) }}">{{ $m->unit->display_name }}</a>@endif
                        @if ($m->supplier) {{ $m->supplier->name }}@endif
                        @if ($m->reference_no)<br><span class="muted">Ref: {{ $m->reference_no }}</span>@endif
                        @if ($m->note)<br><span class="muted">{{ $m->note }}</span>@endif
                        <br><span class="muted">{{ $m->creator?->name }}</span>
                    </td>
                    <td data-label="Masuk" class="num">{{ $in ? $Q($m->qty) : '' }}</td>
                    <td data-label="Keluar" class="num">{{ $in ? '' : $Q($m->qty) }}</td>
                    <td data-label="Harga" class="num">{{ $F::rupiah($m->unit_cost) }}</td>
                    <td data-label="Saldo" class="num"><strong>{{ $Q($m->balance_after) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">Belum ada pergerakan stok.</td></tr>
            @endforelse
            </tbody></table></div></div>
        {{ $movements->links() }}
    </div>
    <aside class="detail-aside stack">
        @if ($canManage)
            <form method="post" action="{{ route('dealer.parts.receive', $part) }}" class="card card-body stack">
                @csrf
                <h2 class="card-title mb-0">Terima barang / pembelian</h2>
                <div class="form-grid cols-2">
                    <x-field name="qty" label="Jumlah ({{ $part->uom }})" type="number" min="0.01" step="0.01" required />
                    <x-field name="unit_cost" label="Harga satuan (Rp)" type="number" min="0" step="100" required />
                </div>
                <x-field name="party_id" label="Pemasok" type="select">
                    <option value="">— Tanpa pemasok —</option>
                    @foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                </x-field>
                @if ($suppliers->isEmpty())<span class="small muted">Belum ada pemasok. Tambahkan di <a href="{{ route('dealer.parties.create', ['peran' => 'supplier']) }}">Nasabah & pemasok</a> dengan peran "Pemasok sparepart".</span>@endif
                <x-field name="reference_no" label="No. nota/faktur" />
                <x-field name="note" label="Catatan" />
                <button class="btn btn-primary">Tambah stok</button>
            </form>
            <form method="post" action="{{ route('dealer.parts.adjust', $part) }}" class="card card-body stack" data-confirm="Sesuaikan stok sistem ke jumlah fisik?">
                @csrf
                <h2 class="card-title mb-0">Stok opname / penyesuaian</h2>
                <x-field name="counted_qty" label="Jumlah fisik saat ini" type="number" min="0" step="0.01" required :value="(float) $part->qty_on_hand" />
                <x-field name="reason" label="Alasan" required placeholder="Contoh: hasil opname, barang rusak" />
                <button class="btn btn-outline">Simpan penyesuaian</button>
            </form>
            <details class="card card-body">
                <summary style="cursor:pointer;font-weight:700">Ubah data sparepart</summary>
                <form method="post" action="{{ route('dealer.parts.update', $part) }}" class="stack mt-2">
                    @csrf @method('put')
                    <x-field name="sku" label="Kode SKU" :value="$part->sku" required />
                    <x-field name="name" label="Nama" :value="$part->name" required />
                    <x-field name="category" label="Kategori" type="select"><option value="">—</option>@foreach (\App\Models\SparePart::CATEGORIES as $k => $v)<option value="{{ $k }}" @selected($part->category === $k)>{{ $v }}</option>@endforeach</x-field>
                    <x-field name="uom" label="Satuan" :value="$part->uom" required />
                    <x-field name="min_stock" label="Stok minimum" type="number" min="0" step="0.01" :value="(float) $part->min_stock" />
                    <x-field name="location" label="Lokasi" :value="$part->location" />
                    <x-field name="compatible_with" label="Cocok untuk" :value="$part->compatible_with" />
                    <x-field name="status" label="Status" type="select"><option value="active" @selected($part->status === 'active')>Aktif</option><option value="inactive" @selected($part->status === 'inactive')>Nonaktif</option></x-field>
                    <button class="btn btn-outline btn-sm">Simpan</button>
                </form>
            </details>
        @endif
    </aside>
</div>
@endsection
