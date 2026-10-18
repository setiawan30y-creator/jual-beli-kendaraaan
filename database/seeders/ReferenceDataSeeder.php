<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Plan;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Data master: kota, paket, katalog awal. Idempoten (aman dijalankan ulang). */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['Jakarta Selatan', 'DKI Jakarta', -6.2615, 106.8106], ['Jakarta Timur', 'DKI Jakarta', -6.2250, 106.9004],
            ['Bandung', 'Jawa Barat', -6.9175, 107.6191], ['Bekasi', 'Jawa Barat', -6.2383, 106.9756],
            ['Depok', 'Jawa Barat', -6.4025, 106.7942], ['Bogor', 'Jawa Barat', -6.5971, 106.8060],
            ['Tangerang', 'Banten', -6.1783, 106.6319], ['Semarang', 'Jawa Tengah', -6.9667, 110.4167],
            ['Yogyakarta', 'DI Yogyakarta', -7.7956, 110.3695], ['Surabaya', 'Jawa Timur', -7.2575, 112.7521],
            ['Malang', 'Jawa Timur', -7.9666, 112.6326], ['Denpasar', 'Bali', -8.6705, 115.2126],
            ['Medan', 'Sumatera Utara', 3.5952, 98.6722], ['Palembang', 'Sumatera Selatan', -2.9761, 104.7754],
            ['Makassar', 'Sulawesi Selatan', -5.1477, 119.4327], ['Balikpapan', 'Kalimantan Timur', -1.2379, 116.8529],
        ];
        foreach ($cities as [$name, $prov, $lat, $lng]) {
            City::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'province' => $prov, 'latitude' => $lat, 'longitude' => $lng]);
        }

        // Harga hipotesis untuk uji pasar (Blueprint §10) — dapat diubah dari admin tanpa deploy.
        $plans = [
            ['gratis', 'Gratis', 0, ['listing_limit' => 5, 'staff_limit' => 2, 'branch_limit' => 1], 1],
            ['starter', 'Starter', 149000, ['listing_limit' => 30, 'staff_limit' => 3, 'branch_limit' => 1], 2],
            ['pro', 'Pro', 399000, ['listing_limit' => 100, 'staff_limit' => 10, 'branch_limit' => 3], 3],
            ['business', 'Business', 799000, ['listing_limit' => 300, 'staff_limit' => 30, 'branch_limit' => 10], 4],
        ];
        foreach ($plans as [$code, $name, $price, $limits, $order]) {
            Plan::firstOrCreate(['code' => $code], [
                'name' => $name, 'price' => $price, 'limits_json' => $limits, 'sort_order' => $order,
                'features_json' => ['crm' => $code !== 'gratis', 'laporan_lanjutan' => in_array($code, ['pro', 'business'], true)],
            ]);
        }

        $catalog = [
            'Honda' => ['motor,mobil', [
                ['motor', 'BeAT', 'matic', 110, 'otomatis'], ['motor', 'Vario 160', 'matic', 160, 'otomatis'], ['motor', 'PCX 160', 'matic', 160, 'otomatis'],
                ['motor', 'Scoopy', 'matic', 110, 'otomatis'], ['motor', 'CBR150R', 'sport', 150, 'manual'], ['motor', 'Supra X 125', 'bebek', 125, 'semi'],
                ['mobil', 'Brio', 'hatchback', 1199, 'otomatis'], ['mobil', 'HR-V', 'suv', 1498, 'otomatis'],
            ]],
            'Yamaha' => ['motor', [
                ['motor', 'NMAX 155', 'matic', 155, 'otomatis'], ['motor', 'Aerox 155', 'matic', 155, 'otomatis'], ['motor', 'Fazzio', 'matic', 125, 'otomatis'],
                ['motor', 'R15', 'sport', 155, 'manual'], ['motor', 'XSR 155', 'naked', 155, 'manual'],
            ]],
            'Suzuki' => ['motor,mobil', [
                ['motor', 'Satria F150', 'bebek', 150, 'manual'], ['motor', 'Address', 'matic', 115, 'otomatis'],
                ['mobil', 'Ertiga', 'mpv', 1462, 'otomatis'], ['mobil', 'XL7', 'suv', 1462, 'otomatis'],
            ]],
            'Kawasaki' => ['motor', [['motor', 'Ninja 250', 'sport', 250, 'manual'], ['motor', 'W175', 'naked', 177, 'manual'], ['motor', 'KLX 150', 'trail', 144, 'manual']]],
            'Toyota' => ['mobil', [
                ['mobil', 'Avanza', 'mpv', 1496, 'otomatis'], ['mobil', 'Innova Zenix', 'mpv', 1987, 'otomatis'],
                ['mobil', 'Raize', 'suv', 998, 'otomatis'], ['mobil', 'Calya', 'mpv', 1197, 'manual'],
            ]],
            'Daihatsu' => ['mobil', [['mobil', 'Xenia', 'mpv', 1329, 'manual'], ['mobil', 'Sigra', 'mpv', 1197, 'manual'], ['mobil', 'Rocky', 'suv', 1198, 'otomatis']]],
            'Mitsubishi' => ['mobil', [['mobil', 'Xpander', 'mpv', 1499, 'otomatis'], ['mobil', 'Pajero Sport', 'suv', 2442, 'otomatis']]],
        ];
        foreach ($catalog as $brandName => [$cats, $models]) {
            $brand = VehicleBrand::firstOrCreate(['slug' => Str::slug($brandName)], ['name' => $brandName, 'categories' => $cats]);
            foreach ($models as [$cat, $name, $body, $cc, $trans]) {
                VehicleModel::firstOrCreate(['slug' => Str::slug($brandName.' '.$name)], [
                    'brand_id' => $brand->id, 'category' => $cat, 'name' => $name, 'body_type' => $body,
                    'engine_cc' => $cc, 'transmission' => $trans, 'fuel_type' => 'bensin',
                ]);
            }
        }
    }
}
