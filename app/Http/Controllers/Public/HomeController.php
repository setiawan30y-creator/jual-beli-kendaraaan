<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Tenant;
use App\Models\VehicleBrand;

class HomeController extends Controller
{
    public function index()
    {
        $base = Listing::publiclyVisible()->where('listings.status', 'published')
            ->with(['unit.media', 'unit.vehicleModel.brand', 'city', 'tenant']);

        return view('public.home', [
            'featured' => (clone $base)->where('is_featured', true)->latest('published_at')->take(4)->get(),
            'latest' => (clone $base)->latest('published_at')->take(8)->get(),
            'brands' => VehicleBrand::where('status', 'active')->orderBy('name')->take(12)->get(),
            'dealers' => Tenant::where('status', 'active')->where('verification_status', 'verified')
                ->withCount(['listings' => fn ($q) => $q->where('status', 'published')])
                ->with('city')->orderByDesc('listings_count')->take(4)->get(),
        ]);
    }
}
