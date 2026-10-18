<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\UnitMedia;
use App\Models\VehicleModel;
use App\Models\VehicleUnit;
use App\Services\ListingService;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Penjual perorangan memasang iklan (Blueprint §4.3). */
class SellController extends Controller
{
    public function create()
    {
        return view('public.sell', [
            'models' => VehicleModel::with('brand')->orderBy('category')->get()->sortBy(fn ($m) => $m->category.$m->fullName())->values(),
            'cities' => City::orderBy('name')->get(),
            'categories' => UnitMedia::CATEGORIES,
        ]);
    }

    public function store(Request $request, ListingService $listings, MediaService $media)
    {
        $upload = ['nullable', 'file', 'mimes:'.implode(',', config('marketplace.uploads.mimes')), 'max:'.config('marketplace.uploads.max_kb')];
        $data = $request->validate([
            'model_id' => ['required', 'exists:vehicle_models,id'],
            'condition' => ['required', Rule::in(['new', 'used'])],
            'year' => ['required', 'integer', 'between:1980,'.(now()->year + 1)],
            'color' => ['nullable', 'string', 'max:40'],
            'odometer_km' => ['nullable', 'integer', 'min:0', 'max:2000000'],
            'price' => ['required', 'numeric', 'min:100000', 'max:100000000000'],
            'city_id' => ['required', 'exists:cities,id'],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'frame_number' => ['nullable', 'string', 'max:40'],
            'engine_number' => ['nullable', 'string', 'max:40'],
            'photos' => ['array'],
            'photos.*' => $upload,
            'photos.depan' => ['required', ...array_slice($upload, 1)],
            'publish_consent' => ['accepted'],
        ], [], ['model_id' => 'model kendaraan', 'publish_consent' => 'persetujuan publikasi foto', 'photos.depan' => 'foto tampak depan']);

        $listing = DB::transaction(function () use ($request, $data, $listings, $media) {
            $unit = new VehicleUnit([
                'model_id' => $data['model_id'], 'condition' => $data['condition'], 'year' => $data['year'],
                'color' => $data['color'] ?? null, 'odometer_km' => $data['odometer_km'] ?? null,
                'asking_price' => $data['price'], 'ownership_type' => 'owned',
            ]);
            $unit->seller_user_id = $request->user()->id;
            $unit->status = 'available';
            $unit->setFrameNumber($data['frame_number'] ?? null);
            $unit->setEngineNumber($data['engine_number'] ?? null);
            $unit->save();

            foreach ((array) $request->file('photos', []) as $category => $file) {
                if ($file && array_key_exists($category, UnitMedia::CATEGORIES)) {
                    $media->store($unit, $category, $file, true);
                }
            }

            return $listings->createForUnit($unit, $request->user(), [
                'title' => $data['title'], 'description' => $data['description'] ?? null,
                'price' => $data['price'], 'city_id' => $data['city_id'],
            ]);
        });

        return redirect()->route('account.listings.show', $listing)
            ->with('status', 'Iklan tersimpan sebagai draf. Lengkapi bukti dokumen bila perlu, lalu ajukan untuk direview.');
    }
}
