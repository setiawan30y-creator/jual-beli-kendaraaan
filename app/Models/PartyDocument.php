<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyDocument extends Model
{
    /** Semua dokumen nasabah bersifat privat (Blueprint: "Keamanan dan privasi"). */
    public const CATEGORIES = [
        'ktp' => ['label' => 'KTP', 'for' => 'perorangan', 'hint' => 'Foto KTP utuh, tidak terpotong dan terbaca jelas.'],
        'sim' => ['label' => 'SIM', 'for' => 'perorangan', 'hint' => 'Identitas alternatif bila diperlukan.'],
        'paspor' => ['label' => 'Paspor', 'for' => 'perorangan', 'hint' => 'Halaman data paspor.'],
        'foto_orang' => ['label' => 'Selfie nasabah', 'for' => 'semua', 'hint' => 'Selfie wajah jelas (disarankan sambil memegang KTP) untuk pencocokan identitas; bukan pengganti dokumen resmi.'],
        'legalitas' => ['label' => 'Dokumen legalitas', 'for' => 'perusahaan', 'hint' => 'NIB/akta/NPWP badan usaha.'],
        'pendukung' => ['label' => 'Dokumen pendukung', 'for' => 'semua', 'hint' => 'Opsional, mis. kartu keluarga atau surat kuasa.'],
    ];

    protected $guarded = ['id'];

    protected $casts = ['verified_at' => 'datetime'];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }
}
