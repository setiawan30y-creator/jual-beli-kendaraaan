<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Tenant;
use Illuminate\Http\Request;

class DealerController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $dealers = Tenant::where('status', 'active')
            ->when($q, fn ($b) => $b->where('name', 'like', '%'.str_replace(['%', '_'], '', $q).'%'))
            ->withCount(['listings' => fn ($b) => $b->where('status', 'published')])
            ->with('city')->orderByDesc('verification_status')->orderBy('name')
            ->paginate(12)->withQueryString();

        return view('public.dealers', compact('dealers', 'q'));
    }

    public function show(Request $request, Tenant $tenant)
    {
        abort_unless($tenant->status === 'active', 404);
        $tenant->load(['city', 'branches' => fn ($q) => $q->where('status', 'active')->with('city')]);
        $kondisi = $request->query('kondisi');
        $listings = Listing::publiclyVisible()->where('listings.tenant_id', $tenant->id)
            ->whereIn('listings.status', ['published', 'reserved'])
            ->when(in_array($kondisi, ['new', 'used'], true), fn ($q) => $q->where('condition', $kondisi))
            ->with(['unit.media', 'unit.vehicleModel.brand', 'city', 'tenant'])
            ->latest('published_at')->paginate(12)->withQueryString();

        return view('public.dealer', ['dealer' => $tenant, 'listings' => $listings, 'kondisi' => $kondisi, 'theme' => $tenant->theme()]);
    }
}
