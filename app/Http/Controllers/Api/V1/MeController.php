<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingPresenter;
use App\Models\Booking;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\User;
use App\Services\LeadService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MeController extends Controller
{
    public static function present(User $u): array
    {
        return [
            'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone,
            'theme_mode' => $u->theme_mode,
            'tenants' => $u->memberships()->where('status', 'active')->with('tenant')->get()
                ->map(fn ($m) => ['slug' => $m->tenant->slug, 'name' => $m->tenant->name, 'role' => $m->role])->values(),
        ];
    }

    public function show(Request $request)
    {
        return ApiResponse::ok(self::present($request->user()));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'regex:/^[0-9+\-\s]{8,20}$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'theme_mode' => ['sometimes', Rule::in(['light', 'dark', 'system'])],
        ]);
        $user->update($data);

        return ApiResponse::ok(self::present($user));
    }

    public function favorites(Request $request)
    {
        $page = $request->user()->favorites()->publiclyVisible()
            ->with(['unit.media', 'unit.vehicleModel.brand', 'city', 'tenant'])->paginate(20);

        return ApiResponse::paginated($page, fn ($l) => ListingPresenter::summary($l));
    }

    public function addFavorite(Request $request)
    {
        $data = $request->validate(['listing_id' => ['required', 'integer']]);
        $listing = Listing::publiclyVisible()->findOrFail($data['listing_id']);
        $request->user()->favorites()->syncWithoutDetaching([$listing->id]);

        return ApiResponse::ok(['listing_id' => $listing->id, 'favorite' => true], [], 201);
    }

    public function removeFavorite(Request $request, int $listingId)
    {
        $request->user()->favorites()->detach($listingId);

        return ApiResponse::ok(['listing_id' => $listingId, 'favorite' => false]);
    }

    public function leads(Request $request)
    {
        $leads = Lead::where('buyer_user_id', $request->user()->id)->with('listing')->latest()->paginate(20);

        return ApiResponse::paginated($leads, fn ($l) => [
            'id' => $l->id, 'status' => $l->status, 'listing' => $l->listing?->title, 'listing_slug' => $l->listing?->slug,
            'created_at' => $l->created_at->toIso8601String(),
        ]);
    }

    public function book(Request $request, LeadService $leads)
    {
        $data = $request->validate([
            'listing_id' => ['required', 'integer'],
            'booking_type' => ['required', Rule::in(array_keys(Booking::TYPES))],
            'starts_at' => ['required', 'date', 'after:now', 'before:+60 days'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $listing = Listing::publiclyVisible()->where('listings.status', 'published')->findOrFail($data['listing_id']);
        $booking = $leads->book($listing, $request->user(), $data);

        return ApiResponse::ok(['id' => $booking->id, 'status' => $booking->status, 'starts_at' => $booking->starts_at->toIso8601String()], [], 201);
    }
}
