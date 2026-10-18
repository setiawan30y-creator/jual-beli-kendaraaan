<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\User;
use App\Models\VehicleUnit;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ListingService
{
    public function __construct(protected PlanLimits $limits) {}

    public function createForUnit(VehicleUnit $unit, User $seller, array $data): Listing
    {
        $unit->loadMissing('vehicleModel.brand');

        $listing = new Listing([
            'unit_id' => $unit->id,
            'category' => $unit->vehicleModel->category,
            'condition' => $unit->condition,
            'title' => $data['title'] ?? $unit->display_name,
            'description' => $data['description'] ?? null,
            'price' => $data['price'] ?? $unit->asking_price,
            'negotiable' => (bool) ($data['negotiable'] ?? true),
            'city_id' => $data['city_id'] ?? $unit->branch?->city_id ?? $unit->tenant?->city_id,
        ]);
        $listing->tenant_id = $unit->tenant_id;
        $listing->seller_user_id = $seller->id;
        $listing->status = 'draft';
        $listing->slug = $this->uniqueSlug($listing->title);
        $listing->save();

        Audit::log('listing.created', $listing, null, ['status' => 'draft']);

        return $listing;
    }

    /** draft/rejected → pending_review, dengan cek kuota paket atau batas penjual perorangan. */
    public function submit(Listing $listing): Listing
    {
        if (! in_array($listing->status, ['draft', 'rejected', 'expired'], true)) {
            throw ValidationException::withMessages(['status' => 'Iklan ini tidak dapat diajukan dari status saat ini.']);
        }

        $unit = $listing->unit()->with('media')->first();
        $hasPhoto = $unit->media->contains(fn ($m) => $m->isPublic());
        if (! $hasPhoto) {
            throw ValidationException::withMessages(['media' => 'Unggah minimal satu foto unit (depan/belakang/kanan/kiri) dan izinkan publikasinya sebelum mengajukan iklan.']);
        }
        if ($unit->status !== 'available') {
            throw ValidationException::withMessages(['unit' => 'Unit harus berstatus Tersedia sebelum diiklankan.']);
        }

        if ($listing->tenant_id) {
            if (! $this->limits->canConsume($listing->tenant, 'listing_limit')) {
                throw ValidationException::withMessages(['plan' => 'Kuota iklan aktif pada paket Anda sudah penuh. Arsipkan iklan lain atau tingkatkan paket.']);
            }
        } else {
            $active = Listing::whereNull('tenant_id')->where('seller_user_id', $listing->seller_user_id)
                ->whereIn('status', ['pending_review', 'published', 'reserved'])->count();
            if ($active >= config('marketplace.private_seller_listing_limit')) {
                throw ValidationException::withMessages(['plan' => 'Batas iklan aktif untuk penjual perorangan sudah tercapai.']);
            }
        }

        return $this->transition($listing, 'pending_review');
    }

    public function approve(Listing $listing, User $moderator): Listing
    {
        abort_unless($listing->status === 'pending_review', 422, 'Hanya iklan yang menunggu review yang dapat disetujui.');

        return DB::transaction(function () use ($listing, $moderator) {
            $listing->reviewed_by = $moderator->id;
            $listing->reviewed_at = now();
            $listing->rejection_reason = null;
            $listing->published_at = now();
            $listing->expires_at = now()->addDays(config('marketplace.listing_ttl_days'));

            return $this->transition($listing, 'published');
        });
    }

    public function reject(Listing $listing, User $moderator, string $reason): Listing
    {
        abort_unless($listing->status === 'pending_review', 422, 'Hanya iklan yang menunggu review yang dapat ditolak.');
        $listing->reviewed_by = $moderator->id;
        $listing->reviewed_at = now();
        $listing->rejection_reason = $reason;

        return $this->transition($listing, 'rejected', $reason);
    }

    public function markSold(Listing $listing): Listing
    {
        return DB::transaction(function () use ($listing) {
            $unit = VehicleUnit::whereKey($listing->unit_id)->lockForUpdate()->first();
            if ($unit->status !== 'sold') {
                $before = $unit->status;
                $unit->status = 'sold';
                $unit->save();
                Audit::log('unit.status_changed', $unit, ['status' => $before], ['status' => 'sold'], 'Iklan ditandai terjual');
            }

            return $this->transition($listing, 'sold');
        });
    }

    public function transition(Listing $listing, string $to, ?string $reason = null): Listing
    {
        $allowed = [
            'draft' => ['pending_review', 'archived'],
            'pending_review' => ['published', 'rejected', 'draft'],
            'rejected' => ['pending_review', 'archived'],
            'published' => ['reserved', 'sold', 'archived', 'expired'],
            'reserved' => ['published', 'sold'],
            'expired' => ['pending_review', 'archived'],
            'sold' => ['archived'],
            'archived' => [],
        ];
        if (! in_array($to, $allowed[$listing->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => "Perubahan status dari {$listing->status} ke {$to} tidak diizinkan."]);
        }
        $from = $listing->status;
        $listing->status = $to;
        $listing->save();
        Audit::log('listing.status_changed', $listing, ['status' => $from], ['status' => $to], $reason);

        return $listing;
    }

    public function uniqueSlug(string $title): string
    {
        $base = Str::slug(Str::limit($title, 70, '')) ?: 'kendaraan';
        do {
            $slug = $base.'-'.Str::lower(Str::random(6));
        } while (Listing::where('slug', $slug)->exists());

        return $slug;
    }
}
