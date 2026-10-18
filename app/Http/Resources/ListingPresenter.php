<?php

namespace App\Http\Resources;

use App\Models\Listing;
use App\Models\Tenant;
use App\Models\VehicleUnit;

/**
 * Representasi API yang aman: tidak pernah mengekspos nomor rangka/mesin, harga beli,
 * catatan internal, atau dokumen privat.
 */
class ListingPresenter
{
    public static function summary(Listing $l): array
    {
        return [
            'id' => $l->id,
            'slug' => $l->slug,
            'title' => $l->title,
            'category' => $l->category,
            'condition' => $l->condition === 'new' ? 'baru' : 'seken',
            'price' => (float) $l->price,
            'negotiable' => $l->negotiable,
            'status' => $l->status,
            'is_featured' => $l->is_featured,
            'year' => $l->unit?->year,
            'odometer_km' => $l->unit?->odometer_km,
            'brand' => $l->unit?->vehicleModel?->brand?->name,
            'model' => $l->unit?->vehicleModel?->name,
            'city' => $l->city?->name,
            'cover_url' => $l->coverUrl(),
            'seller_type' => $l->tenant_id ? 'dealer' : 'perorangan',
            'dealer' => $l->tenant ? ['name' => $l->tenant->name, 'slug' => $l->tenant->slug, 'verified' => $l->tenant->isVerified()] : null,
            'published_at' => $l->published_at?->toIso8601String(),
            'url' => route('listings.show', $l),
        ];
    }

    public static function detail(Listing $l): array
    {
        $m = $l->unit?->vehicleModel;

        return self::summary($l) + [
            'description' => $l->description,
            'color' => $l->unit?->color,
            'variant' => $l->unit?->variant?->name,
            'specifications' => [
                'body_type' => $m?->body_type, 'engine_cc' => $m?->engine_cc,
                'transmission' => $m?->transmission, 'fuel_type' => $m?->fuel_type,
            ],
            'registration_status' => $l->unit?->registration_status,
            'photos' => $l->publicPhotos()->map(fn ($p) => ['category' => $p->category, 'url' => $p->url()])->values(),
        ];
    }

    public static function dealer(Tenant $t): array
    {
        return [
            'name' => $t->name, 'slug' => $t->slug, 'verified' => $t->isVerified(),
            'city' => $t->city?->name, 'description' => $t->description,
            'phone' => $t->phone, 'whatsapp' => $t->whatsapp,
            'url' => route('dealers.show', $t),
        ];
    }

    /** Unit untuk anggota tenant; data biaya hanya untuk yang berizin. */
    public static function unit(VehicleUnit $u, bool $withCosts): array
    {
        $data = [
            'id' => $u->id, 'stock_code' => $u->stock_code, 'name' => $u->display_name,
            'condition' => $u->condition, 'year' => $u->year, 'color' => $u->color, 'odometer_km' => $u->odometer_km,
            'status' => $u->status, 'ownership_type' => $u->ownership_type, 'asking_price' => (float) $u->asking_price,
            'frame_number_masked' => VehicleUnit::mask($u->frameNumber()),
            'branch' => $u->branch?->name, 'listing_status' => $u->listing?->status,
        ];
        if ($withCosts) {
            $data['purchase_price'] = $u->purchase_price !== null ? (float) $u->purchase_price : null;
        }

        return $data;
    }
}
