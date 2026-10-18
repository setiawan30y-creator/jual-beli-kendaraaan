<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\VehicleUnit;
use App\Services\ListingService;
use App\Services\PlanLimits;
use App\Support\Audit;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    public function __construct(protected TenantContext $ctx, protected ListingService $service) {}

    public function index(Request $request, PlanLimits $limits)
    {
        $this->ctx->authorize('units.view');
        $status = $request->query('status');
        $listings = $this->ctx->tenant()->listings()
            ->with(['unit.vehicleModel.brand', 'unit.media'])
            ->withCount('leads')
            ->when(array_key_exists((string) $status, Listing::STATUSES), fn ($q) => $q->where('status', $status))
            ->latest()->paginate(15)->withQueryString();

        return view('dealer.listings.index', [
            'listings' => $listings, 'status' => $status,
            'usage' => $limits->summary($this->ctx->tenant())['listing_limit'],
            'canManage' => $this->ctx->can('listings.manage'),
        ]);
    }

    public function store(Request $request, VehicleUnit $unit)
    {
        $this->ctx->ensureOwns($unit);
        $this->ctx->authorize('listings.manage');
        abort_if($unit->listing && ! in_array($unit->listing->status, ['archived', 'sold', 'expired'], true), 422, 'Unit ini sudah memiliki iklan aktif.');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:100000'],
            'negotiable' => ['nullable', 'boolean'],
        ]);
        $listing = $this->service->createForUnit($unit, $request->user(), $data);

        return redirect()->route('dealer.units.show', $unit)->with('status', 'Draf iklan dibuat: '.$listing->title);
    }

    public function update(Request $request, Listing $listing)
    {
        $this->ctx->ensureOwns($listing);
        $this->ctx->authorize('listings.manage');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:100000'],
            'negotiable' => ['nullable', 'boolean'],
        ]);
        $before = $listing->only(['title', 'price']);
        $listing->fill($data + ['negotiable' => $request->boolean('negotiable')])->save();
        Audit::log('listing.updated', $listing, $before, $listing->only(['title', 'price']));

        return back()->with('status', 'Iklan diperbarui.');
    }

    public function submit(Listing $listing)
    {
        $this->ctx->ensureOwns($listing);
        $this->ctx->authorize('listings.manage');
        $this->service->submit($listing);

        return back()->with('status', 'Iklan diajukan untuk review moderator.');
    }

    public function sold(Listing $listing)
    {
        $this->ctx->ensureOwns($listing);
        $this->ctx->authorize('listings.manage');
        $this->service->markSold($listing);

        return back()->with('status', 'Iklan & unit ditandai terjual.');
    }

    public function archive(Listing $listing)
    {
        $this->ctx->ensureOwns($listing);
        $this->ctx->authorize('listings.manage');
        $this->service->transition($listing, 'archived', 'Diarsipkan dealer');

        return back()->with('status', 'Iklan diarsipkan.');
    }
}
