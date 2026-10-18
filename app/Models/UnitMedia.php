<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitMedia extends Model
{
    protected $table = 'unit_media';

    /** Kategori bukti/foto (Blueprint §4.2.1). */
    public const CATEGORIES = [
        'bpkb' => ['label' => 'BPKB', 'private' => true, 'hint' => 'Foto halaman BPKB untuk verifikasi.'],
        'stnk' => ['label' => 'STNK', 'private' => true, 'hint' => 'Foto STNK untuk verifikasi.'],
        'nomor_rangka' => ['label' => 'Nomor rangka', 'private' => true, 'hint' => 'Nomor rangka terbaca jelas.'],
        'nomor_mesin' => ['label' => 'Nomor mesin', 'private' => true, 'hint' => 'Nomor mesin terbaca jelas.'],
        'depan' => ['label' => 'Tampak depan', 'private' => false, 'hint' => 'Seluruh bagian depan terlihat.'],
        'belakang' => ['label' => 'Tampak belakang', 'private' => false, 'hint' => 'Seluruh bagian belakang terlihat.'],
        'kanan' => ['label' => 'Samping kanan', 'private' => false, 'hint' => 'Ambil dari sisi kanan, sejajar.'],
        'kiri' => ['label' => 'Samping kiri', 'private' => false, 'hint' => 'Ambil dari sisi kiri, sejajar.'],
        'orang_unit' => ['label' => 'Orang + unit', 'private' => true, 'hint' => 'Bukti tambahan; tidak dipublikasikan otomatis.'],
    ];

    public const STATUS_LABELS = [
        'belum_diunggah' => 'Belum diunggah',
        'menunggu_verifikasi' => 'Menunggu verifikasi',
        'terverifikasi' => 'Terverifikasi',
        'perlu_perbaikan' => 'Perlu perbaikan',
    ];

    protected $guarded = ['id'];

    protected $casts = ['publish_consent' => 'boolean', 'verified_at' => 'datetime'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(VehicleUnit::class, 'unit_id');
    }

    public function isPrivateCategory(): bool
    {
        return self::CATEGORIES[$this->category]['private'] ?? true;
    }

    /** Hanya foto unit dengan izin publikasi yang boleh tampil di galeri publik. */
    public function isPublic(): bool
    {
        return $this->disk === 'public' && ! $this->isPrivateCategory() && $this->publish_consent;
    }

    public function url(): string
    {
        return $this->isPublic()
            ? asset('storage/'.$this->path)
            : route('media.private', $this);
    }
}
