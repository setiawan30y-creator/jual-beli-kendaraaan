{{-- VehicleMediaUploader: unggahan per kategori bukti/foto (Blueprint §4.2.1, UI §17) --}}
@props(['unit', 'checklist', 'canManage' => false, 'canPrivate' => false, 'canVerify' => false, 'verifyRoute' => null])
<div class="uploader">
    @foreach ($checklist as $key => $slot)
        @php $private = $slot['private']; $showFiles = ! $private || $canPrivate; @endphp
        <div class="slot {{ $private ? 'is-private' : '' }}">
            <div class="slot-head">
                <span>
                    <strong>{{ $slot['label'] }}</strong>
                    <span class="small muted">{{ $slot['hint'] }}</span>
                </span>
                <x-status-badge :status="$slot['status']" :label="\App\Models\UnitMedia::STATUS_LABELS[$slot['status']]" />
            </div>
            @if ($private)<span class="badge badge-warning" style="align-self:flex-start"><x-icon name="lock" />Privat — hanya untuk verifikasi</span>@endif

            @if ($slot['items']->isNotEmpty())
                <div class="slot-files">
                    @foreach ($slot['items'] as $m)
                        <div class="slot-file">
                            @if ($showFiles)
                                <a href="{{ $m->url() }}" target="_blank" rel="noopener"><img src="{{ $m->url() }}" alt="{{ $slot['label'] }}" loading="lazy"></a>
                            @else
                                <div class="ph" style="width:96px;height:72px;border-radius:8px"><x-icon name="lock" /></div>
                            @endif
                            <div class="meta muted">{{ $m->created_at->format('d/m/y H:i') }}</div>
                            @if (! $private)
                                <div class="meta">{!! $m->publish_consent ? '<span class="badge badge-success">Publik</span>' : '<span class="badge">Tidak publik</span>' !!}</div>
                            @endif
                            @if ($canManage)
                                @if (! $private)
                                    <form method="post" action="{{ route('media.consent', $m) }}">@csrf @method('patch')
                                        <input type="hidden" name="publish_consent" value="{{ $m->publish_consent ? 0 : 1 }}">
                                        <button class="btn btn-text btn-sm" style="padding:0;min-height:28px">{{ $m->publish_consent ? 'Jangan tampilkan' : 'Tampilkan publik' }}</button>
                                    </form>
                                @endif
                                <form method="post" action="{{ route('media.destroy', $m) }}" data-confirm="Hapus berkas {{ $slot['label'] }}?">@csrf @method('delete')
                                    @if ($m->verification_status === 'terverifikasi')<input type="hidden" name="confirm_verified" value="1">@endif
                                    <button class="btn btn-text btn-sm" style="padding:0;min-height:28px;color:var(--danger)">Hapus</button>
                                </form>
                            @endif
                            @if ($canVerify && $verifyRoute)
                                <form method="post" action="{{ route($verifyRoute, $m) }}" class="row" style="gap:4px">@csrf
                                    <button class="btn btn-text btn-sm" style="padding:0;min-height:28px" name="verification_status" value="terverifikasi">Verifikasi</button>
                                    <button class="btn btn-text btn-sm" style="padding:0;min-height:28px;color:var(--danger)" name="verification_status" value="perlu_perbaikan">Perlu perbaikan</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($canManage && $slot['items']->count() < config('marketplace.uploads.per_category'))
                <form method="post" action="{{ route('media.store', $unit) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="category" value="{{ $key }}">
                    <input class="file-input" type="file" name="file" required accept="image/jpeg,image/png,image/webp" data-preview="pv-{{ $unit->id }}-{{ $key }}" data-max-kb="{{ config('marketplace.uploads.max_kb') }}" aria-label="Pilih berkas {{ $slot['label'] }}">
                    <div class="slot-preview" id="pv-{{ $unit->id }}-{{ $key }}" aria-live="polite"></div>
                    @if (! $private)
                        <label class="check small"><input type="checkbox" name="publish_consent" value="1" @checked($key !== 'orang_unit')> <span>Izinkan tampil di iklan publik</span></label>
                    @endif
                    <button class="btn btn-outline btn-sm"><x-icon name="camera" class="icon icon-sm" />{{ $slot['items']->isEmpty() ? 'Unggah' : 'Tambah' }}</button>
                </form>
            @endif
        </div>
    @endforeach
</div>
<p class="small muted mt-1">Format JPG/PNG/WebP, maks. {{ config('marketplace.uploads.max_kb') / 1024 }} MB, {{ config('marketplace.uploads.per_category') }} berkas per kategori. Berkas tersimpan setelah tombol unggah berhasil diproses.</p>
