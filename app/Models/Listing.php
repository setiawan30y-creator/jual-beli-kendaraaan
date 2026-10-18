<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listing extends Model
{
    public const STATUSES = [
        'draft' => 'Draf', 'pending_review' => 'Menunggu review', 'published' => 'Tayang', 'rejected' => 'Ditolak',
        'reserved' => 'Dipesan', 'sold' => 'Terjual', 'expired' => 'Kedaluwarsa', 'archived' => 'Diarsipkan',
    ];

    public const SORTS = [
        'terbaru' => 'Terbaru', 'termurah' => 'Harga termurah', 'termahal' => 'Harga tertinggi', 'tahun' => 'Tahun terbaru',
    ];

    protected $guarded = ['id', 'tenant_id', 'seller_user_id', 'status', 'reviewed_by', 'reviewed_at', 'published_at', 'is_featured', 'views_count'];

    protected $casts = [
        'price' => 'decimal:2', 'negotiable' => 'boolean', 'is_featured' => 'boolean',
        'published_at' => 'datetime', 'expires_at' => 'datetime', 'reviewed_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(VehicleUnit::class, 'unit_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ListingReport::class);
    }

    /** Listing yang boleh tampil di halaman publik. */
    public function scopePubliclyVisible(Builder $q): Builder
    {
        return $q->whereIn('listings.status', ['published', 'reserved', 'sold'])
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn ($q) => $q->whereNull('listings.tenant_id')->orWhereHas('tenant', fn ($t) => $t->where('status', 'active')));
    }

    public function isAvailable(): bool
    {
        return $this->status === 'published';
    }

    /** Foto publik yang telah diizinkan untuk tayang (urut: depan, kanan, kiri, belakang). */
    public function publicPhotos()
    {
        $order = ['depan', 'kanan', 'kiri', 'belakang'];

        return ($this->unit?->media ?? collect())
            ->filter(fn (UnitMedia $m) => $m->isPublic())
            ->sortBy(fn ($m) => array_search($m->category, $order, true))
            ->values();
    }

    public function coverUrl(): ?string
    {
        return $this->publicPhotos()->first()?->url();
    }

    public function whatsappNumber(): ?string
    {
        $raw = $this->tenant?->whatsapp ?: $this->tenant?->phone ?: $this->seller?->phone;
        if (! $raw) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $raw);

        return str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits;
    }
}
