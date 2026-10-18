@extends('layouts.admin')
@section('heading', 'Master katalog')
@section('content')
<h1>Master katalog</h1>
<div class="grid grid-2 mb-2">
    <form method="post" action="{{ route('admin.catalog.brands.store') }}" class="card card-body stack">@csrf
        <h2 class="card-title mb-0">Tambah merek</h2>
        <x-field name="name" label="Nama merek" required />
        <x-field name="categories" label="Kategori" type="select"><option value="motor">Motor</option><option value="mobil">Mobil</option><option value="motor,mobil">Motor & mobil</option></x-field>
        <button class="btn btn-primary">Simpan merek</button>
    </form>
    <form method="post" action="{{ route('admin.catalog.models.store') }}" class="card card-body stack">@csrf
        <h2 class="card-title mb-0">Tambah model</h2>
        <div class="form-grid cols-2">
            <x-field name="brand_id" label="Merek" type="select" required>@foreach ($brands as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</x-field>
            <x-field name="category" label="Kategori" type="select"><option value="motor">Motor</option><option value="mobil">Mobil</option></x-field>
            <x-field name="name" label="Nama model" required />
            <x-field name="body_type" label="Tipe bodi" placeholder="matic, sport, mpv, suv" />
            <x-field name="engine_cc" label="Kapasitas mesin (cc)" type="number" />
            <x-field name="transmission" label="Transmisi" type="select"><option value="">—</option><option value="manual">Manual</option><option value="otomatis">Otomatis</option><option value="semi">Semi</option></x-field>
        </div>
        <button class="btn btn-primary">Simpan model</button>
    </form>
</div>
<div class="card"><div class="table-wrap"><table class="table stacked">
    <thead><tr><th>Merek</th><th>Model</th><th>Kategori</th><th>Tipe</th><th class="num">cc</th><th>Transmisi</th></tr></thead>
    <tbody>@foreach ($models as $m)<tr><td data-label="Merek">{{ $m->brand->name }}</td><td data-label="Model">{{ $m->name }}</td><td data-label="Kategori">{{ $m->category }}</td><td data-label="Tipe">{{ $m->body_type }}</td><td data-label="cc" class="num">{{ $m->engine_cc }}</td><td data-label="Transmisi">{{ $m->transmission }}</td></tr>@endforeach</tbody>
</table></div></div>
{{ $models->links() }}
@endsection
