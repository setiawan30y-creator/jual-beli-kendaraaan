<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingPresenter;
use App\Models\Listing;
use App\Models\Tenant;
use App\Services\LeadService;
use App\Services\ListingSearch;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    public function index(Request $request, ListingSearch $search)
    {
        $filters = $search->normalize($request->query());
        $per = min(max($request->integer('per_page', 20), 1), 50);
        $page = $search->query($filters)->with(['unit.media', 'unit.vehicleModel.brand', 'city', 'tenant'])->paginate($per);

        return ApiResponse::paginated($page, fn ($l) => ListingPresenter::summary($l));
    }

    public function show(Listing $listing)
    {
        abort_unless(Listing::publiclyVisible()->whereKey($listing->id)->exists(), 404, 'Iklan tidak ditemukan.');
        $listing->load(['unit.media', 'unit.vehicleModel.brand', 'unit.variant', 'city', 'tenant']);

        return ApiResponse::ok(ListingPresenter::detail($listing));
    }

    public function dealer(Tenant $tenant)
    {
        abort_unless($tenant->status === 'active', 404, 'Dealer tidak ditemukan.');

        return ApiResponse::ok(ListingPresenter::dealer($tenant->load('city')));
    }

    public function lead(Request $request, Listing $listing, LeadService $leads)
    {
        abort_unless($listing->status === 'published' && Listing::publiclyVisible()->whereKey($listing->id)->exists(), 404, 'Iklan tidak tersedia.');
        $data = $request->validate([
            'buyer_name' => ['required', 'string', 'max:100'],
            'buyer_phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'message' => ['nullable', 'string', 'max:1000'],
            'contact_consent' => ['accepted'],
        ]);
        $user = auth('sanctum')->user();
        $lead = $leads->create($listing, $data + ['source' => 'api', 'buyer_user_id' => $user?->id]);

        return ApiResponse::ok(['id' => $lead->id, 'status' => $lead->status], [], 201);
    }
}
