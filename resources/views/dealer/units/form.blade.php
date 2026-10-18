@extends('layouts.dealer')
@section('heading', $unit->exists ? 'Ubah unit' : 'Tambah unit')
@section('content')
<h1>{{ $unit->exists ? 'Ubah unit' : 'Tambah unit' }}</h1>
<form method="post" action="{{ $unit->exists ? route('dealer.units.update', $unit) : route('dealer.units.store') }}" class="stack" style="max-width:920px">
    @csrf @if ($unit->exists) @method('put') @endif
    <fieldset class="fieldset"><legend>Identitas kendaraan</legend>
        <div class="form-grid cols-2">
            <x-field name="model_id" label="Merek & model" type="select" required class="span-2">
                <option value="">Pilih model</option>
                @foreach ($models->groupBy('category') as $cat => $group)
                    <optgroup label="{{ ucfirst($cat) }}">@foreach ($group as $m)<option value="{{ $m->id }}" @selected(old('model_id', $unit->model_id) == $m->id)>{{ $m->fullName() }}</option>@endforeach</optgroup>
                @endforeach
            </x-field>
            <x-field name="condition" label="Kondisi" type="select" required>
                <option value="used" @selected(old('condition', $unit->condition) === 'used')>Seken</option>
                <option value="new" @selected(old('condition', $unit->condition) === 'new')>Baru</option>
            </x-field>
            <x-field name="year" label="Tahun" type="number" required min="1980" :max="now()->year + 1" :value="$unit->year" />
            <x-field name="color" label="Warna" :value="$unit->color" />
            <x-field name="odometer_km" label="Kilometer" type="number" min="0" :value="$unit->odometer_km" />
            <x-field name="frame_number" label="Nomor rangka" :hint="$unit->exists ? 'Tersimpan: '.\App\Models\VehicleUnit::mask($unit->frameNumber()).'. Kosongkan bila tidak diubah.' : 'Disimpan terenkripsi; tidak tampil publik.'" />
            <x-field name="engine_number" label="Nomor mesin" :hint="$unit->exists ? 'Tersimpan: '.\App\Models\VehicleUnit::mask($unit->engineNumber()).'. Kosongkan bila tidak diubah.' : 'Disimpan terenkripsi; tidak tampil publik.'" />
            <x-field name="plate_region" label="Kode wilayah plat" :value="$unit->plate_region" placeholder="B, D, L, ..." />
            <x-field name="registration_status" label="Status dokumen" type="select">
                <option value="">—</option>
                @foreach (['lengkap' => 'BPKB & STNK lengkap', 'stnk_hidup' => 'STNK hidup', 'stnk_mati' => 'STNK mati/pajak telat', 'proses' => 'Dalam proses', 'baru' => 'Unit baru'] as $k => $v)
                    <option value="{{ $k }}" @selected(old('registration_status', $unit->registration_status) === $k)>{{ $v }}</option>
                @endforeach
            </x-field>
        </div>
    </fieldset>
    <fieldset class="fieldset"><legend>Stok & harga</legend>
        <div class="form-grid cols-2">
            <x-field name="stock_code" label="Kode stok" :value="$unit->stock_code" />
            <x-field name="branch_id" label="Cabang" type="select">
                @foreach ($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id', $unit->branch_id) == $b->id)>{{ $b->name }}</option>@endforeach
            </x-field>
            <x-field name="ownership_type" label="Kepemilikan" type="select" required hint="Unit titipan (konsinyasi) tidak dihitung sebagai aset milik dealer.">
                <option value="owned" @selected(old('ownership_type', $unit->ownership_type ?? 'owned') === 'owned')>Milik dealer</option>
                <option value="consignment" @selected(old('ownership_type', $unit->ownership_type) === 'consignment')>Titipan/konsinyasi</option>
            </x-field>
            <x-field name="acquired_at" label="Tanggal masuk stok" type="date" :value="$unit->acquired_at?->toDateString()" />
            @if ($canCosts && ! $unit->exists)
                <x-field name="purchase_price" label="Harga beli (internal)" type="number" min="0" step="50000" hint="Otomatis diposting ke kartu HPP unit." />
            @endif
            <x-field name="asking_price" label="Harga jual target" type="number" required min="100000" step="50000" :value="$unit->asking_price ? (int) $unit->asking_price : null" />
            <x-field name="internal_notes" label="Catatan internal" type="textarea" :value="$unit->internal_notes" class="span-2" hint="Tidak ditampilkan publik." />
        </div>
    </fieldset>
    <div class="row"><button class="btn btn-primary">{{ $unit->exists ? 'Simpan perubahan' : 'Simpan unit' }}</button><a class="btn btn-outline" href="{{ $unit->exists ? route('dealer.units.show', $unit) : route('dealer.units.index') }}">Batal</a></div>
</form>
@endsection
