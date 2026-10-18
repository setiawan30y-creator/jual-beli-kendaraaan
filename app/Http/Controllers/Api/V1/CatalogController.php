<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function brands(Request $request)
    {
        $cat = in_array($request->query('category'), ['motor', 'mobil'], true) ? $request->query('category') : null;
        $brands = VehicleBrand::where('status', 'active')
            ->when($cat, fn ($q) => $q->where('categories', 'like', "%{$cat}%"))
            ->orderBy('name')->get(['id', 'name', 'slug', 'categories']);

        return ApiResponse::ok($brands);
    }

    public function models(Request $request)
    {
        $models = VehicleModel::with('brand:id,name')
            ->when($request->integer('brand_id'), fn ($q, $id) => $q->where('brand_id', $id))
            ->when(in_array($request->query('category'), ['motor', 'mobil'], true), fn ($q) => $q->where('category', $request->query('category')))
            ->orderBy('name')->get()
            ->map(fn ($m) => ['id' => $m->id, 'brand' => $m->brand->name, 'name' => $m->name, 'category' => $m->category,
                'body_type' => $m->body_type, 'engine_cc' => $m->engine_cc, 'transmission' => $m->transmission]);

        return ApiResponse::ok($models);
    }

    public function locations()
    {
        return ApiResponse::ok(City::orderBy('name')->get(['id', 'name', 'province', 'slug']));
    }
}
