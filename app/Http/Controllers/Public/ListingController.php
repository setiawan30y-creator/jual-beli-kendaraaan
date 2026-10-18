<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\ListingReport;
use App\Services\LeadService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ListingController extends Controller
{
    public function show(Request $request, Listing $listing)
    {
        $visible = Listing::publiclyVisible()->whereKey($listing->id)->exists();
        $user = $request->user();
        $isOwner = $user && ($listing->seller_user_id === $user->id || ($listing->tenant_id && $user->activeMembershipFor($listing->tenant_id)));
        abort_unless($visible || $isOwner || $user?->canModerate(), 404);

        $listing->load(['unit.media', 'unit.vehicleModel.brand', 'unit.variant', 'city', 'tenant.city', 'seller']);
        if ($visible && ! $isOwner) {
            Listing::whereKey($listing->id)->increment('views_count');
        }

        $similar = Listing::publiclyVisible()->where('listings.status', 'published')
            ->where('listings.id', '!=', $listing->id)
            ->where('category', $listing->category)
            ->whereHas('unit', fn ($q) => $q->where('model_id', $listing->unit->model_id)
                ->orWhereHas('vehicleModel', fn ($m) => $m->where('brand_id', $listing->unit->vehicleModel->brand_id)))
            ->with(['unit.media', 'unit.vehicleModel.brand', 'city', 'tenant'])
            ->latest('published_at')->take(4)->get();

        return view('public.listing', [
            'listing' => $listing,
            'photos' => $listing->publicPhotos(),
            'similar' => $similar,
            'isFavorite' => $user ? $user->favorites()->whereKey($listing->id)->exists() : false,
            'isPreview' => ! $visible,
        ]);
    }

    public function lead(Request $request, Listing $listing, LeadService $leads)
    {
        abort_unless($listing->status === 'published' && Listing::publiclyVisible()->whereKey($listing->id)->exists(), 404);
        $data = $request->validate([
            'buyer_name' => ['required', 'string', 'max:100'],
            'buyer_phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'message' => ['nullable', 'string', 'max:1000'],
            'contact_consent' => ['accepted'],
            'source' => ['nullable', Rule::in(['form', 'whatsapp'])],
        ], [], ['buyer_name' => 'nama', 'buyer_phone' => 'nomor telepon', 'contact_consent' => 'persetujuan dihubungi']);

        $lead = $leads->create($listing, $data + ['buyer_user_id' => $request->user()?->id]);

        if (($data['source'] ?? 'form') === 'whatsapp' && ($wa = $listing->whatsappNumber())) {
            $text = rawurlencode("Halo, saya tertarik dengan iklan \"{$listing->title}\" ".route('listings.show', $listing));

            return redirect()->away("https://wa.me/{$wa}?text={$text}");
        }

        return back()->with('status', 'Terima kasih! Penjual akan menghubungi Anda. Nomor referensi minat: #'.$lead->id);
    }

    public function report(Request $request, Listing $listing)
    {
        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(ListingReport::REASONS))],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $report = $listing->reports()->create($data + ['reporter_user_id' => $request->user()?->id]);
        Audit::log('listing.reported', $report, null, ['reason' => $data['reason']], null, $listing->tenant_id);

        return back()->with('status', 'Laporan diterima. Tim moderasi akan meninjau iklan ini.');
    }

    public function toggleFavorite(Request $request, Listing $listing)
    {
        abort_unless(Listing::publiclyVisible()->whereKey($listing->id)->exists(), 404);
        $result = $request->user()->favorites()->toggle($listing->id);
        $added = ! empty($result['attached']);

        if ($request->expectsJson()) {
            return response()->json(['favorite' => $added]);
        }

        return back()->with('status', $added ? 'Disimpan ke favorit.' : 'Dihapus dari favorit.');
    }

    public function book(Request $request, Listing $listing, LeadService $leads)
    {
        abort_unless($listing->status === 'published' && Listing::publiclyVisible()->whereKey($listing->id)->exists(), 404);
        $data = $request->validate([
            'booking_type' => ['required', Rule::in(array_keys(Booking::TYPES))],
            'starts_at' => ['required', 'date', 'after:now', 'before:+60 days'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], ['starts_at' => 'jadwal']);

        $leads->book($listing, $request->user(), $data);

        return back()->with('status', 'Permintaan jadwal terkirim. Penjual akan mengonfirmasi.');
    }
}
