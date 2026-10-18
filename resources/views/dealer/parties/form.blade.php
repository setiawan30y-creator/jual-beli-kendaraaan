@extends('layouts.dealer')
@section('heading', $party->exists ? 'Ubah data' : 'Tambah nasabah / pemasok')
@section('content')
@php $type = old('type', $party->type ?? 'perorangan'); $roles = old('roles', $party->roles ?? ['customer']); @endphp
<h1>{{ $party->exists ? 'Ubah data '.$party->name : 'Tambah nasabah / pemasok' }}</h1>
@if (session('duplicate'))
    <div class="notice notice-warning mb-2"><x-icon name="warning" /><div>Kemungkinan data ganda: <a href="{{ route('dealer.parties.show', session('duplicate')['id']) }}" target="_blank"><strong>{{ session('duplicate')['name'] }}</strong></a> {{ session('duplicate')['phone'] ? '('.session('duplicate')['phone'].')' : '' }}. Gunakan data yang sudah ada agar riwayat transaksi tidak terpecah.</div></div>
@endif
<form method="post" action="{{ $party->exists ? route('dealer.parties.update', $party) : route('dealer.parties.store') }}" class="stack" style="max-width:920px" data-party-form>
    @csrf @if ($party->exists) @method('put') @endif
    <fieldset class="fieldset"><legend>Jenis & peran</legend>
        <div class="stack">
            <div class="segmented" role="radiogroup" aria-label="Jenis pihak">
                <label><input type="radio" name="type" value="perorangan" @checked($type === 'perorangan') data-type-radio><span>Perorangan</span></label>
                <label><input type="radio" name="type" value="perusahaan" @checked($type === 'perusahaan') data-type-radio><span>Perusahaan</span></label>
            </div>
            <div class="field"><span class="label">Peran (boleh lebih dari satu)</span>
                <div class="row">
                    @foreach (\App\Models\Party::ROLES as $k => $v)
                        <label class="check"><input type="checkbox" name="roles[]" value="{{ $k }}" @checked(in_array($k, $roles, true))> <span>{{ $v }}</span></label>
                    @endforeach
                </div>
                @error('roles')<span class="error">{{ $message }}</span>@enderror
            </div>
        </div>
    </fieldset>
    <fieldset class="fieldset"><legend>Data utama</legend>
        <div class="form-grid cols-2">
            <x-field name="name" label="Nama lengkap / nama badan usaha" :value="$party->name" required class="span-2" placeholder="Nama lengkap" />
            <x-field name="id_type" label="Jenis identitas" type="select">
                <option value="">—</option>
                @foreach (\App\Models\Party::ID_TYPES as $group => $opts)
                    @foreach ($opts as $k => $v)<option value="{{ $k }}" data-for="{{ $group }}" @selected(old('id_type', $party->id_type) === $k)>{{ $v }}</option>@endforeach
                @endforeach
            </x-field>
            <x-field name="id_number" label="Nomor identitas" autocomplete="off" inputmode="text"
                :hint="$party->exists && $party->id_number_last4 ? 'Tersimpan: '.$party->maskedIdNumber().'. Kosongkan bila tidak diubah.' : 'Disimpan terenkripsi; ditampilkan tersamar.'" placeholder="Contoh: 16 digit NIK" />
            <div class="field" data-for-type="perorangan"><label for="f_birth_place">Tempat lahir</label><input id="f_birth_place" class="input" name="birth_place" value="{{ old('birth_place', $party->birth_place) }}"></div>
            <div class="field" data-for-type="perorangan"><label for="f_birth_date">Tanggal lahir</label><input id="f_birth_date" class="input" type="date" name="birth_date" value="{{ old('birth_date', $party->birth_date?->toDateString()) }}"></div>
            <x-field name="phone" label="Telepon / WhatsApp" type="tel" :value="$party->phone" placeholder="08xxxxxxxxxx" />
            <x-field name="email" label="Email" type="email" :value="$party->email" />
            <x-field name="address" label="Alamat" :value="$party->address" class="span-2" />
            <x-field name="city_id" label="Kota" type="select">
                <option value="">—</option>
                @foreach ($cities as $c)<option value="{{ $c->id }}" @selected(old('city_id', $party->city_id) == $c->id)>{{ $c->name }}</option>@endforeach
            </x-field>
            <div class="field" data-for-type="perusahaan"><label for="f_pic_name">Nama perwakilan (PIC)</label><input id="f_pic_name" class="input" name="pic_name" value="{{ old('pic_name', $party->pic_name) }}" placeholder="Nama perwakilan"></div>
            <div class="field" data-for-type="perusahaan"><label for="f_pic_phone">Telepon perwakilan</label><input id="f_pic_phone" class="input" name="pic_phone" value="{{ old('pic_phone', $party->pic_phone) }}"></div>
            <x-field name="notes" label="Catatan internal" type="textarea" :value="$party->notes" class="span-2" />
        </div>
    </fieldset>
    @if (session('duplicate'))
        <label class="check"><input type="checkbox" name="confirm_duplicate" value="1"> <span>Saya sudah memeriksa dan tetap ingin menyimpan sebagai data baru.</span></label>
    @endif
    <p class="small muted mb-0">Dokumen KTP/SIM/paspor dan foto nasabah diunggah di halaman detail setelah data tersimpan. Pastikan nasabah sudah diberi tahu dan menyetujui penggunaan datanya.</p>
    <div class="row"><button class="btn btn-primary">{{ $party->exists ? 'Simpan perubahan' : 'Simpan' }}</button>
        <a class="btn btn-outline" href="{{ $party->exists ? route('dealer.parties.show', $party) : route('dealer.parties.index') }}">Batal</a></div>
</form>
@endsection
@push('scripts')
<script>
(function () {
  const form = document.querySelector('[data-party-form]'); if (!form) return;
  const apply = () => {
    const type = form.querySelector('[data-type-radio]:checked')?.value || 'perorangan';
    form.querySelectorAll('[data-for-type]').forEach((el) => { el.hidden = el.dataset.forType !== type; });
    const sel = form.querySelector('[name="id_type"]');
    sel.querySelectorAll('option[data-for]').forEach((o) => { o.hidden = o.dataset.for !== type; o.disabled = o.dataset.for !== type; });
    if (sel.selectedOptions[0]?.disabled) sel.value = '';
  };
  form.addEventListener('change', (e) => { if (e.target.matches('[data-type-radio]')) apply(); });
  apply();
})();
</script>
@endpush
