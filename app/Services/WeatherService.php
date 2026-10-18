<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Widget cuaca: data dari penyedia yang dikonfigurasi. Jika gagal, kembalikan null —
 * UI menampilkan status "tidak tersedia", tidak mengarang kondisi cuaca.
 */
class WeatherService
{
    public const CODES = [
        0 => ['Cerah', 'sun'], 1 => ['Cerah berawan', 'cloud-sun'], 2 => ['Berawan sebagian', 'cloud-sun'], 3 => ['Berawan', 'cloud'],
        45 => ['Berkabut', 'fog'], 48 => ['Berkabut', 'fog'], 51 => ['Gerimis', 'drizzle'], 53 => ['Gerimis', 'drizzle'], 55 => ['Gerimis lebat', 'drizzle'],
        61 => ['Hujan ringan', 'rain'], 63 => ['Hujan', 'rain'], 65 => ['Hujan lebat', 'rain'], 80 => ['Hujan lokal', 'rain'], 81 => ['Hujan lokal', 'rain'],
        82 => ['Hujan deras', 'rain'], 95 => ['Badai petir', 'storm'], 96 => ['Badai petir', 'storm'], 99 => ['Badai petir', 'storm'],
    ];

    public function current(?float $lat, ?float $lng): ?array
    {
        if (! config('marketplace.weather.enabled') || $lat === null || $lng === null) {
            return null;
        }
        $key = sprintf('weather:%.2f:%.2f', $lat, $lng);

        $data = Cache::get($key);
        if ($data) {
            return $data;
        }

        try {
            $response = Http::timeout(4)->get(config('marketplace.weather.provider_url'), [
                'latitude' => $lat, 'longitude' => $lng,
                'current' => 'temperature_2m,weather_code',
                'temperature_unit' => config('marketplace.weather.unit'),
                'timezone' => 'Asia/Jakarta',
            ]);
            if (! $response->ok() || ! isset($response['current']['temperature_2m'])) {
                return null;
            }
            $code = (int) $response['current']['weather_code'];
            [$label, $icon] = self::CODES[$code] ?? ['Tidak diketahui', 'cloud'];
            $data = [
                'temperature' => round((float) $response['current']['temperature_2m']),
                'unit' => config('marketplace.weather.unit') === 'fahrenheit' ? '°F' : '°C',
                'label' => $label,
                'icon' => $icon,
                'updated_at' => now()->format('H:i'),
            ];
            Cache::put($key, $data, now()->addMinutes(config('marketplace.weather.cache_minutes')));

            return $data;
        } catch (\Throwable) {
            return null;
        }
    }
}
