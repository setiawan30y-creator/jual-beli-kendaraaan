@extends('layouts.dealer')
@section('heading', $party->name)
@section('content')
@php
    $statusTone = ['belum_lengkap' => 'draft', 'ditolak' => 'rejected'][$party->identity_status] ?? $party->identity_status;
    $cats = collect(\App\Models\PartyDocument::CATEGORIES)->filter(fn ($c) => $c['for'] === 'semua' || $c['for'] === $party->type);
    $docs = $party->documents->groupBy('category');
@endphp
<nav class="breadcrumb"><a href="{{ route('dealer.parties.index') }}">Nasabah & pemasok</a><span>›</span><span>{{ $party->name }}</span></nav>
<div class="row-between mb-2">
    <div>
        <h1 class="mb-0">{{ $party->name }}</h1>
        <div class="row small">
            <span class="badge">{{ ucfirst($party->type) }}</span>
            @foreach ($party->roleLabels() as $r)<span class="badge badge-info">{{ $r }}</span>@endforeach
            <x-status-badge :status="$statusTone" :label="'Identitas: '.\App\Models\Party::IDENTITY_STATUSES[$party->identity_status]" />
        </div>
    </div>
    @if ($canManage)<a class="btn btn-outline" href="{{ route('dealer.parties.edit', $party) }}">Ubah data</a>@endif
</div>
@if ($party->identity_status === 'ditolak' && $party->identity_note)
    <div class="notice notice-danger mb-2"><x-icon name="warning" /><div><strong>Identitas ditolak:</strong> {{ $party->identity_note }}</div></div>
@elseif ($party->identity_status === 'belum_lengkap')
    <div class="notice notice-warning mb-2"><x-icon name="info" /><div>Identitas belum lengkap. Unggah {{ $party->type === 'perusahaan' ? 'dokumen legalitas' : 'KTP (atau SIM/paspor)' }} dan isi nomor identitas sebelum dipakai untuk transaksi.</div></div>
@endif

<div class="detail">
    <div class="stack">
        <div class="card card-body">
            <h2 class="card-title">Dokumen identitas <span class="badge badge-warning"><x-icon name="lock" />Privat</span></h2>
            @unless ($canIdentity)<p class="small muted">Anda dapat mengunggah dokumen, tetapi tidak berizin membuka isinya.</p>@endunless
            <div class="uploader">
                @foreach ($cats as $key => $cat)
                    @php $items = $docs->get($key, collect()); @endphp
                    <div class="slot is-private">
                        <div class="slot-head"><span><strong>{{ $cat['label'] }}</strong><span class="small muted">{{ $cat['hint'] }}</span></span>
                            <x-status-badge :status="$items->isEmpty() ? 'draft' : ($items->every(fn ($d) => $d->verification_status === 'terverifikasi') ? 'terverifikasi' : 'menunggu_verifikasi')"
                                :label="$items->isEmpty() ? 'Belum diunggah' : ($items->every(fn ($d) => $d->verification_status === 'terverifikasi') ? 'Terverifikasi' : 'Menunggu verifikasi')" /></div>
                        @if ($items->isNotEmpty())
                            <div class="slot-files">
                                @foreach ($items as $d)
                                    <div class="slot-file">
                                        @if ($canIdentity)
                                            <a href="{{ route('dealer.parties.documents.show', $d) }}" target="_blank" rel="noopener">
                                                @if ($d->isImage())<img src="{{ route('dealer.parties.documents.show', $d) }}" alt="{{ $cat['label'] }}" loading="lazy">@else<div class="ph" style="width:96px;height:72px;border-radius:8px"><x-icon name="receipt" /></div>@endif
                                            </a>
                                        @else
                                            <div class="ph" style="width:96px;height:72px;border-radius:8px"><x-icon name="lock" /></div>
                                        @endif
                                        <div class="meta muted">{{ $d->created_at->format('d/m/y H:i') }}</div>
                                        @if ($canManage)
                                            <form method="post" action="{{ route('dealer.parties.documents.destroy', $d) }}" data-confirm="Hapus dokumen {{ $cat['label'] }}?">@csrf @method('delete')
                                                @if ($d->verification_status === 'terverifikasi')<input type="hidden" name="confirm_verified" value="1">@endif
                                                <button class="btn btn-text btn-sm" style="padding:0;min-height:28px;color:var(--danger)">Hapus</button></form>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if ($canManage && $items->count() < config('marketplace.uploads.per_category'))
                            <form method="post" action="{{ route('dealer.parties.documents.store', $party) }}" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="category" value="{{ $key }}">
                                <input class="file-input" type="file" name="file" required accept="image/jpeg,image/png,image/webp,application/pdf" data-preview="pv-party-{{ $key }}" data-allow-pdf="1" data-max-kb="{{ config('marketplace.uploads.max_kb') }}" aria-label="Pilih berkas {{ $cat['label'] }}">
                                <div class="slot-preview" id="pv-party-{{ $key }}" aria-live="polite"></div>
                                <button class="btn btn-outline btn-sm"><x-icon name="camera" class="icon icon-sm" />Unggah</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
            <p class="small muted mt-1 mb-0">JPG/PNG/WebP/PDF, maks. {{ config('marketplace.uploads.max_kb') / 1024 }} MB. Foto nasabah bukan pengganti dokumen identitas resmi. Setiap pembukaan dokumen tercatat di log audit.</p>
        </div>

        <div class="card card-body">
            <h2 class="card-title">Riwayat minat / lead</h2>
            @forelse ($leads as $l)
                <a class="row-between mb-2" href="{{ route('dealer.leads.show', $l) }}" style="color:inherit">
                    <span><strong>{{ $l->listing?->title }}</strong><br><span class="small muted">{{ ucfirst($l->source) }} · {{ $l->created_at->format('d/m/Y H:i') }}</span></span>
                    <x-status-badge :status="$l->status" :label="\App\Models\Lead::STATUSES[$l->status]" />
                </a>
            @empty<p class="muted mb-0">Belum ada riwayat lead untuk nasabah ini.</p>@endforelse
        </div>

        <div class="card card-body">
            <h2 class="card-title">Aktivitas</h2>
            <ul class="timeline small">
                @forelse ($audit as $a)
                    <li><strong>{{ $a->action }}</strong> · {{ $a->actor?->name ?? 'Sistem' }} · <span class="muted">{{ $a->created_at->format('d/m/Y H:i') }}</span>@if($a->reason)<br><span class="muted">{{ $a->reason }}</span>@endif</li>
                @empty<li class="muted">Belum ada aktivitas.</li>@endforelse
            </ul>
        </div>
    </div>

    <aside class="detail-aside stack">
        <div class="card card-body">
            <h2 class="card-title">Data {{ $party->type === 'perusahaan' ? 'perusahaan' : 'nasabah' }}</h2>
            <dl class="kv">
                <dt>{{ $party->idTypeLabel() ?? 'Identitas' }}</dt><dd>{{ $canIdentity ? ($party->idNumber() ?? '—') : $party->maskedIdNumber() }}</dd>
                @if ($party->type === 'perorangan')<dt>Tempat, tgl lahir</dt><dd>{{ $party->birth_place ?? '—' }}{{ $party->birth_date ? ', '.$party->birth_date->format('d/m/Y') : '' }}</dd>@endif
                <dt>Telepon</dt><dd>@if($party->phone)<a href="tel:{{ $party->phone }}">{{ $party->phone }}</a>@else — @endif</dd>
                <dt>Email</dt><dd>{{ $party->email ?? '—' }}</dd>
                <dt>Alamat</dt><dd>{{ $party->address ?? '—' }}{{ $party->city ? ', '.$party->city->name : '' }}</dd>
                @if ($party->type === 'perusahaan')<dt>Perwakilan</dt><dd>{{ $party->pic_name ?? '—' }} {{ $party->pic_phone }}</dd>@endif
                <dt>Diinput oleh</dt><dd>{{ $party->creator?->name ?? '—' }} · {{ $party->created_at->format('d/m/Y') }}</dd>
                @if ($party->identity_verified_at)<dt>Diverifikasi</dt><dd>{{ $party->identity_verified_at->format('d/m/Y H:i') }}</dd>@endif
            </dl>
            @if ($party->notes)<p class="small mt-2 mb-0" style="white-space:pre-line">{{ $party->notes }}</p>@endif
        </div>
        @if ($canVerify)
            <form method="post" action="{{ route('dealer.parties.verify', $party) }}" class="card card-body stack">
                @csrf
                <h2 class="card-title mb-0">Verifikasi identitas</h2>
                <p class="small muted mb-0">Cocokkan nama, nomor identitas, dan foto pada dokumen dengan data di atas.</p>
                <x-field name="identity_status" label="Keputusan" type="select">
                    <option value="terverifikasi">Terverifikasi</option>
                    <option value="ditolak">Ditolak</option>
                    <option value="kedaluwarsa">Kedaluwarsa</option>
                    <option value="menunggu_verifikasi">Kembalikan ke menunggu</option>
                </x-field>
                <x-field name="identity_note" label="Catatan (wajib bila ditolak)" />
                <button class="btn btn-primary">Simpan keputusan</button>
            </form>
        @endif
    </aside>
</div>
@endsection
