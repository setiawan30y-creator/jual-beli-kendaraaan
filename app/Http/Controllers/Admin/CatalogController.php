<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CatalogController extends Controller
{
    public function index()
    {
        return view('admin.catalog', [
            'brands' => VehicleBrand::withCount('models')->orderBy('name')->get(),
            'models' => VehicleModel::with('brand')->orderBy('brand_id')->orderBy('name')->paginate(40),
        ]);
    }

    public function storeBrand(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', 'unique:vehicle_brands,name'],
            'categories' => ['required', Rule::in(['motor', 'mobil', 'motor,mobil'])],
        ]);
        $brand = VehicleBrand::create($data + ['slug' => Str::slug($data['name'])]);
        Audit::log('catalog.brand_created', $brand, null, $data);

        return back()->with('status', 'Merek ditambahkan.');
    }

    public function storeModel(Request $request)
    {
        $data = $request->validate([
            'brand_id' => ['required', 'exists:vehicle_brands,id'],
            'category' => ['required', Rule::in(['motor', 'mobil'])],
            'name' => ['required', 'string', 'max:80'],
            'body_type' => ['nullable', 'string', 'max:40'],
            'engine_cc' => ['nullable', 'integer', 'min:50', 'max:10000'],
            'transmission' => ['nullable', Rule::in(['manual', 'otomatis', 'semi'])],
            'fuel_type' => ['nullable', 'string', 'max:20'],
        ]);
        $brand = VehicleBrand::find($data['brand_id']);
        $slug = Str::slug($brand->name.' '.$data['name']);
        abort_if(VehicleModel::where('slug', $slug)->exists(), 422, 'Model sudah ada.');
        $model = VehicleModel::create($data + ['slug' => $slug]);
        Audit::log('catalog.model_created', $model, null, $data);

        return back()->with('status', 'Model ditambahkan.');
    }
}
