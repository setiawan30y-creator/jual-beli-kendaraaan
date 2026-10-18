{{-- Filter katalog; dipakai di sidebar desktop & bottom sheet mobile --}}
<form method="get" action="{{ url()->current() }}" class="stack" data-filter-form>
    @if ($filters['q'])<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
    <input type="hidden" name="urut" value="{{ $filters['urut'] }}">
    <div class="field"><label for="{{ $p }}kategori">Kategori</label>
        <select id="{{ $p }}kategori" name="kategori" class="select">
            <option value="">Motor & mobil</option>
            <option value="motor" @selected($filters['kategori'] === 'motor')>Motor</option>
            <option value="mobil" @selected($filters['kategori'] === 'mobil')>Mobil</option>
        </select>
    </div>
    <div class="field"><label for="{{ $p }}kondisi">Kondisi</label>
        <select id="{{ $p }}kondisi" name="kondisi" class="select">
            <option value="">Baru & seken</option>
            <option value="baru" @selected($filters['kondisi'] === 'baru')>Baru</option>
            <option value="seken" @selected($filters['kondisi'] === 'seken')>Seken</option>
        </select>
    </div>
    <div class="field"><label for="{{ $p }}merek">Merek</label>
        <select id="{{ $p }}merek" name="merek" class="select">
            <option value="">Semua merek</option>
            @foreach ($brands as $b)<option value="{{ $b->id }}" @selected($filters['merek'] === $b->id)>{{ $b->name }}</option>@endforeach
        </select>
    </div>
    <div class="field"><span class="label">Harga (Rp)</span>
        <div class="row" style="flex-wrap:nowrap">
            <input class="input" type="number" inputmode="numeric" min="0" step="100000" name="harga_min" value="{{ $filters['harga_min'] }}" placeholder="Min" aria-label="Harga minimum">
            <input class="input" type="number" inputmode="numeric" min="0" step="100000" name="harga_max" value="{{ $filters['harga_max'] }}" placeholder="Maks" aria-label="Harga maksimum">
        </div>
    </div>
    <div class="field"><span class="label">Tahun</span>
        <div class="row" style="flex-wrap:nowrap">
            <input class="input" type="number" min="1980" max="{{ now()->year + 1 }}" name="tahun_min" value="{{ $filters['tahun_min'] }}" placeholder="Dari" aria-label="Tahun dari">
            <input class="input" type="number" min="1980" max="{{ now()->year + 1 }}" name="tahun_max" value="{{ $filters['tahun_max'] }}" placeholder="Sampai" aria-label="Tahun sampai">
        </div>
    </div>
    <div class="field"><label for="{{ $p }}km">Kilometer maks (seken)</label>
        <input id="{{ $p }}km" class="input" type="number" min="0" step="1000" name="km_max" value="{{ $filters['km_max'] }}" placeholder="Contoh: 20000">
    </div>
    <div class="field"><label for="{{ $p }}transmisi">Transmisi</label>
        <select id="{{ $p }}transmisi" name="transmisi" class="select">
            <option value="">Semua</option>
            <option value="otomatis" @selected($filters['transmisi'] === 'otomatis')>Otomatis/matic</option>
            <option value="manual" @selected($filters['transmisi'] === 'manual')>Manual</option>
            <option value="semi" @selected($filters['transmisi'] === 'semi')>Semi-otomatis</option>
        </select>
    </div>
    <div class="field"><label for="{{ $p }}kota">Lokasi</label>
        <select id="{{ $p }}kota" name="kota" class="select">
            <option value="">Semua kota</option>
            @foreach ($cities as $c)<option value="{{ $c->id }}" @selected($filters['kota'] === $c->id)>{{ $c->name }}</option>@endforeach
        </select>
    </div>
    <div class="field"><label for="{{ $p }}penjual">Penjual</label>
        <select id="{{ $p }}penjual" name="penjual" class="select">
            <option value="">Dealer & perorangan</option>
            <option value="dealer" @selected($filters['penjual'] === 'dealer')>Dealer</option>
            <option value="perorangan" @selected($filters['penjual'] === 'perorangan')>Perorangan</option>
        </select>
    </div>
    <div class="row">
        <button class="btn btn-primary" style="flex:1">Terapkan</button>
        <a class="btn btn-outline" href="{{ url()->current() }}">Reset</a>
    </div>
</form>
