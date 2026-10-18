<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Listing;
use App\Models\VehicleBrand;
use App\Services\ListingSearch;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public const PRESETS = [
        'motor' => ['kategori' => 'motor', 'title' => 'Motor baru & seken'],
        'motor-baru' => ['kategori' => 'motor', 'kondisi' => 'baru', 'title' => 'Motor baru'],
        'motor-seken' => ['kategori' => 'motor', 'kondisi' => 'seken', 'title' => 'Motor seken'],
        'mobil' => ['kategori' => 'mobil', 'title' => 'Mobil baru & bekas'],
    ];

    public function index(Request $request, ListingSearch $search)
    {
        return $this->render($request, $search, $request->query(), 'Semua kendaraan');
    }

    public function preset(Request $request, ListingSearch $search, string $preset)
    {
        $p = self::PRESETS[$preset];
        $filters = array_merge($request->query(), array_diff_key($p, ['title' => 1]));

        return $this->render($request, $search, $filters, $p['title']);
    }

    protected function render(Request $request, ListingSearch $search, array $filters, string $title)
    {
        $filters = $search->normalize($filters);
        $listings = $search->query($filters)
            ->with(['unit.media', 'unit.vehicleModel.brand', 'city', 'tenant'])
            ->paginate(12)->withQueryString();

        return view('public.catalog', [
            'title' => $title,
            'filters' => $filters,
            'activeFilterCount' => count(array_filter(array_diff_key($filters, ['urut' => 1, 'q' => 1]), fn ($v) => $v !== null && $v !== '')),
            'listings' => $listings,
            'brands' => VehicleBrand::where('status', 'active')->orderBy('name')->get(),
            'cities' => City::orderBy('name')->get(),
            'sorts' => Listing::SORTS,
        ]);
    }
}
