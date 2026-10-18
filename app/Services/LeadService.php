<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;

class LeadService
{
    public function create(Listing $listing, array $data): Lead
    {
        return DB::transaction(function () use ($listing, $data) {
            $lead = new Lead([
                'listing_id' => $listing->id,
                'buyer_user_id' => $data['buyer_user_id'] ?? null,
                'seller_user_id' => $listing->seller_user_id,
                'source' => $data['source'] ?? 'form',
                'buyer_name' => $data['buyer_name'],
                'buyer_phone' => $data['buyer_phone'],
                'message' => $data['message'] ?? null,
                'contact_consent' => true,
            ]);
            $lead->tenant_id = $listing->tenant_id;
            $lead->save();
            $lead->activities()->create(['activity_type' => 'dibuat', 'notes' => 'Lead masuk dari '.$lead->source, 'actor_user_id' => $data['buyer_user_id'] ?? null]);
            Audit::log('lead.created', $lead, null, ['source' => $lead->source]);

            return $lead;
        });
    }

    public function book(Listing $listing, User $buyer, array $data): Booking
    {
        return DB::transaction(function () use ($listing, $buyer, $data) {
            $booking = new Booking([
                'listing_id' => $listing->id, 'buyer_user_id' => $buyer->id,
                'booking_type' => $data['booking_type'], 'starts_at' => $data['starts_at'], 'notes' => $data['notes'] ?? null,
            ]);
            $booking->tenant_id = $listing->tenant_id;
            $booking->status = 'menunggu';
            $booking->save();

            $this->create($listing, [
                'buyer_user_id' => $buyer->id, 'source' => 'booking',
                'buyer_name' => $buyer->name, 'buyer_phone' => $buyer->phone ?: '-',
                'message' => 'Permintaan '.Booking::TYPES[$booking->booking_type].' pada '.$booking->starts_at->format('d/m/Y H:i'),
            ]);

            return $booking;
        });
    }
}
