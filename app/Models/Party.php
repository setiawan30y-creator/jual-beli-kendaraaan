<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

/** Nasabah / sumber unit / pemasok — satu master pihak dengan peran multi. */
class Party extends Model
{
    public const ROLES = [
        'customer' => 'Nasabah/pembeli',
        'vehicle_source' => 'Sumber unit (pemilik/dealer lain)',
        'auction_house' => 'Balai lelang',
        'supplier' => 'Pemasok sparepart',
        'service_vendor' => 'Vendor jasa',
        'business_partner' => 'Mitra usaha',
    ];

    public const ID_TYPES = [
        'perorangan' => ['ktp' => 'KTP (NIK)', 'sim' => 'SIM', 'paspor' => 'Paspor'],
        'perusahaan' => ['nib' => 'NIB', 'npwp' => 'NPWP badan'],
    ];

    public const IDENTITY_STATUSES = [
        'belum_lengkap' => 'Belum lengkap',
        'menunggu_verifikasi' => 'Menunggu verifikasi',
        'terverifikasi' => 'Terverifikasi',
        'ditolak' => 'Ditolak',
        'kedaluwarsa' => 'Kedaluwarsa',
    ];

    protected $guarded = ['id', 'tenant_id', 'id_number_encrypted', 'id_number_hash', 'id_number_last4',
        'identity_status', 'identity_verified_by', 'identity_verified_at', 'created_by'];

    protected $hidden = ['id_number_encrypted', 'id_number_hash'];

    protected $casts = ['roles' => 'array', 'birth_date' => 'date', 'identity_verified_at' => 'datetime'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PartyDocument::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function hashIdNumber(string $value): string
    {
        return hash_hmac('sha256', self::normalizeIdNumber($value), (string) config('app.key'));
    }

    public static function normalizeIdNumber(string $value): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value));
    }

    public function setIdNumber(?string $value): void
    {
        $value = $value ? self::normalizeIdNumber($value) : null;
        $this->id_number_encrypted = $value ? Crypt::encryptString($value) : null;
        $this->id_number_hash = $value ? self::hashIdNumber($value) : null;
        $this->id_number_last4 = $value ? substr($value, -4) : null;
    }

    public function idNumber(): ?string
    {
        return $this->id_number_encrypted ? Crypt::decryptString($this->id_number_encrypted) : null;
    }

    /** Nomor identitas tersamar untuk tabel/layar ringkas. */
    public function maskedIdNumber(): string
    {
        return $this->id_number_last4 ? '••••••••••••'.$this->id_number_last4 : '—';
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles ?? [], true);
    }

    public function roleLabels(): array
    {
        return array_map(fn ($r) => self::ROLES[$r] ?? $r, $this->roles ?? []);
    }

    public function idTypeLabel(): ?string
    {
        return self::ID_TYPES[$this->type][$this->id_type] ?? null;
    }

    /** Kategori dokumen identitas utama sesuai jenis pihak. */
    public function identityCategories(): array
    {
        return $this->type === 'perusahaan' ? ['legalitas'] : ['ktp', 'sim', 'paspor'];
    }
}
