<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Crypt;

class VehicleUnit extends Model
{
    public const STATUSES = [
        'inspection' => 'Inspeksi', 'maintenance' => 'Perbaikan', 'available' => 'Tersedia',
        'reserved' => 'Dipesan', 'sold' => 'Terjual', 'withdrawn' => 'Ditarik',
    ];

    /** Allowed transitions — state changes are validated server-side (Blueprint §6). */
    public const TRANSITIONS = [
        'inspection' => ['maintenance', 'available', 'withdrawn'],
        'maintenance' => ['inspection', 'available', 'withdrawn'],
        'available' => ['reserved', 'sold', 'maintenance', 'withdrawn'],
        'reserved' => ['available', 'sold'],
        'sold' => [],
        'withdrawn' => ['inspection', 'available'],
    ];

    protected $guarded = ['id', 'tenant_id', 'frame_number_encrypted', 'engine_number_encrypted', 'frame_number_hash', 'status'];

    protected $hidden = ['frame_number_encrypted', 'engine_number_encrypted', 'frame_number_hash', 'purchase_price', 'internal_notes'];

    protected $casts = ['purchase_price' => 'decimal:2', 'asking_price' => 'decimal:2', 'acquired_at' => 'date'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(TenantBranch::class, 'branch_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(VehicleVariant::class, 'variant_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(UnitMedia::class, 'unit_id');
    }

    public function costs(): HasMany
    {
        return $this->hasMany(VehicleCostEntry::class, 'unit_id');
    }

    public function listing(): HasOne
    {
        return $this->hasOne(Listing::class, 'unit_id')->latestOfMany();
    }

    public function setFrameNumber(?string $value): void
    {
        $value = $value ? strtoupper(preg_replace('/\s+/', '', $value)) : null;
        $this->frame_number_encrypted = $value ? Crypt::encryptString($value) : null;
        $this->frame_number_hash = $value ? hash_hmac('sha256', $value, (string) config('app.key')) : null;
    }

    public function setEngineNumber(?string $value): void
    {
        $value = $value ? strtoupper(preg_replace('/\s+/', '', $value)) : null;
        $this->engine_number_encrypted = $value ? Crypt::encryptString($value) : null;
    }

    public function frameNumber(): ?string
    {
        return $this->frame_number_encrypted ? Crypt::decryptString($this->frame_number_encrypted) : null;
    }

    public function engineNumber(): ?string
    {
        return $this->engine_number_encrypted ? Crypt::decryptString($this->engine_number_encrypted) : null;
    }

    public static function mask(?string $value): string
    {
        if (! $value) {
            return '—';
        }

        return str_repeat('•', max(strlen($value) - 4, 0)).substr($value, -4);
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    protected function displayName(): Attribute
    {
        return Attribute::get(fn () => trim(($this->vehicleModel?->fullName() ?? 'Kendaraan').' '.($this->variant?->name ?? '').' '.$this->year));
    }

    /** HPP berjalan = biaya yang sudah diposting (estimasi dipisah). */
    public function costSummary(): array
    {
        // Entri asli yang dibalik tetap dihitung, karena entri reversal negatifnya juga diposting.
        $reversedIds = $this->costs->pluck('reverses_id')->filter()->all();
        $posted = $this->costs->filter(fn ($c) => $c->status === 'posted' || ($c->status === 'reversed' && in_array($c->id, $reversedIds)));
        $estimate = $this->costs->where('status', 'estimate');
        $hpp = (float) $posted->sum('amount');
        $price = (float) $this->asking_price;

        return [
            'by_category' => $posted->groupBy('category')->map(fn ($c) => (float) $c->sum('amount'))->all(),
            'hpp' => $hpp,
            'estimate' => (float) $estimate->sum('amount'),
            'price' => $price,
            'gross_profit' => $price - $hpp,
            'margin' => $price > 0 ? round(($price - $hpp) / $price * 100, 1) : null,
        ];
    }
}
