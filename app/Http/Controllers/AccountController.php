<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Lead;
use App\Models\Listing;
use App\Services\ListingService;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('account.index', [
            'user' => $user,
            'favoriteCount' => $user->favorites()->count(),
            'listingCount' => $user->listings()->whereNull('tenant_id')->count(),
            'bookings' => Booking::where('buyer_user_id', $user->id)->with('listing')->latest()->take(5)->get(),
            'memberships' => $user->memberships()->where('status', 'active')->with('tenant')->get(),
        ]);
    }

    public function favorites(Request $request)
    {
        $listings = $request->user()->favorites()->publiclyVisible()
            ->with(['unit.media', 'unit.vehicleModel.brand', 'city', 'tenant'])
            ->orderByPivot('created_at', 'desc')->paginate(12);

        return view('account.favorites', compact('listings'));
    }

    public function listings(Request $request)
    {
        $listings = $request->user()->listings()->whereNull('tenant_id')
            ->with(['unit.media', 'unit.vehicleModel.brand', 'city'])->latest()->paginate(12);

        return view('account.listings', compact('listings'));
    }

    public function showListing(Request $request, Listing $listing, MediaService $media)
    {
        $this->authorizeOwn($request, $listing);
        $listing->load(['unit.media', 'unit.vehicleModel.brand', 'city', 'leads' => fn ($q) => $q->latest()]);

        return view('account.listing-show', ['listing' => $listing, 'checklist' => $media->checklist($listing->unit)]);
    }

    public function submitListing(Request $request, Listing $listing, ListingService $service)
    {
        $this->authorizeOwn($request, $listing);
        $service->submit($listing);

        return back()->with('status', 'Iklan diajukan dan menunggu review moderator.');
    }

    public function soldListing(Request $request, Listing $listing, ListingService $service)
    {
        $this->authorizeOwn($request, $listing);
        $service->markSold($listing);

        return back()->with('status', 'Iklan ditandai terjual.');
    }

    public function archiveListing(Request $request, Listing $listing, ListingService $service)
    {
        $this->authorizeOwn($request, $listing);
        $service->transition($listing, 'archived', 'Diarsipkan pemilik');

        return redirect()->route('account.listings')->with('status', 'Iklan diarsipkan.');
    }

    public function leads(Request $request)
    {
        $user = $request->user();
        $incoming = Lead::whereNull('tenant_id')->where('seller_user_id', $user->id)->with('listing')->latest()->paginate(15, ['*'], 'masuk');
        $outgoing = Lead::where('buyer_user_id', $user->id)->with('listing')->latest()->take(20)->get();
        $bookings = Booking::where('buyer_user_id', $user->id)->with('listing')->latest()->take(20)->get();

        return view('account.leads', compact('incoming', 'outgoing', 'bookings'));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9+\-\s]{8,20}$/', Rule::unique('users', 'phone')->ignore($user->id)],
        ], [], ['name' => 'nama', 'phone' => 'nomor telepon']);
        $user->update($data);

        return back()->with('status', 'Profil diperbarui.');
    }

    public function updateTheme(Request $request)
    {
        $data = $request->validate(['theme_mode' => ['required', Rule::in(['light', 'dark', 'system'])]]);
        $request->user()->update($data);

        return back()->with('status', 'Mode tampilan disimpan.');
    }

    protected function authorizeOwn(Request $request, Listing $listing): void
    {
        abort_unless($listing->tenant_id === null && $listing->seller_user_id === $request->user()->id, 404);
    }
}
