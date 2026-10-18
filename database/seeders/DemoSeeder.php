<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantBranch;
use App\Models\TenantMembership;
use App\Models\UnitMedia;
use App\Models\User;
use App\Models\VehicleCostEntry;
use App\Models\VehicleModel;
use App\Models\VehicleUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Data demo dengan nama netral/generik. Semua akun demo memakai kata sandi: password123
 * Jangan jalankan di production.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'admin@example.com')->exists()) {
            return; // sudah pernah di-seed
        }
        $pw = 'password123';
        $admin = $this->user('Admin Platform', 'admin@example.com', $pw, 'super_admin');
        $this->user('Moderator Platform', 'moderator@example.com', $pw, 'moderator');
        $owner = $this->user('Pemilik Dealer', 'dealer@example.com', $pw, 'user', '081200000001');
        $sales = $this->user('Staf Sales', 'sales@example.com', $pw, 'user', '081200000002');
        $owner2 = $this->user('Pemilik Showroom', 'showroom@example.com', $pw, 'user', '081200000003');
        $seller = $this->user('Penjual Perorangan', 'penjual@example.com', $pw, 'user', '081200000004');
        $buyer = $this->user('Pembeli Demo', 'pembeli@example.com', $pw, 'user', '081200000005');

        $dealerA = $this->tenant('Dealer Motor Contoh', $owner, 'Jakarta Selatan', 'pro', 'verified',
            'Dealer motor baru dan seken. Unit seken melalui inspeksi internal sebelum dijual.');
        $dealerB = $this->tenant('Showroom Mobil Contoh', $owner2, 'Surabaya', 'starter', 'verified',
            'Showroom mobil bekas berkualitas dengan dokumen lengkap.', ['primary' => '#13795b', 'accent' => '#e0a100']);
        TenantMembership::create(['tenant_id' => $dealerA->id, 'user_id' => $sales->id, 'branch_id' => $dealerA->branches()->first()->id, 'role' => 'sales', 'status' => 'active', 'joined_at' => now()]);

        $units = [
            [$dealerA, 'honda-vario-160', 'used', 2023, 'Hitam', 8500, 24500000, 21000000, true],
            [$dealerA, 'yamaha-nmax-155', 'used', 2022, 'Putih', 15200, 26500000, 23000000, false],
            [$dealerA, 'honda-beat', 'new', 2026, 'Merah', 0, 19100000, 17600000, false],
            [$dealerA, 'kawasaki-ninja-250', 'used', 2021, 'Hijau', 21000, 52000000, 46000000, true],
            [$dealerA, 'honda-scoopy', 'used', 2023, 'Krem', 6300, 20500000, 17800000, false],
            [$dealerA, 'yamaha-fazzio', 'new', 2026, 'Biru muda', 0, 22900000, 21200000, false],
            [$dealerB, 'toyota-avanza', 'used', 2021, 'Silver', 48000, 189000000, 168000000, true],
            [$dealerB, 'mitsubishi-xpander', 'used', 2022, 'Putih', 35000, 228000000, 205000000, false],
            [$dealerB, 'honda-brio', 'used', 2020, 'Kuning', 52000, 142000000, 126000000, false],
            [$dealerB, 'daihatsu-rocky', 'used', 2023, 'Merah', 18000, 218000000, 196000000, false],
        ];
        foreach ($units as $i => [$tenant, $modelSlug, $cond, $year, $color, $km, $price, $buy, $featured]) {
            $listing = $this->unitWithListing($tenant, $tenant->owner, $modelSlug, $cond, $year, $color, $km, $price, $buy, $i);
            $listing->is_featured = $featured;
            $listing->save();
        }

        // Iklan penjual perorangan: satu tayang, satu menunggu review (untuk demo moderasi)
        $this->unitWithListing(null, $seller, 'suzuki-satria-f150', 'used', 2019, 'Biru', 31000, 15500000, null, 20);
        $pending = $this->unitWithListing(null, $seller, 'honda-pcx-160', 'used', 2022, 'Abu-abu', 12000, 27500000, null, 21);
        $pending->status = 'pending_review';
        $pending->published_at = null;
        $pending->save();

        $first = Listing::where('tenant_id', $dealerA->id)->first();
        $lead = new Lead([
            'listing_id' => $first->id, 'buyer_user_id' => $buyer->id, 'seller_user_id' => $owner->id, 'source' => 'form',
            'buyer_name' => $buyer->name, 'buyer_phone' => $buyer->phone, 'message' => 'Apakah unit masih tersedia? Bisa test ride hari Sabtu?',
            'contact_consent' => true, 'assigned_user_id' => $sales->id,
        ]);
        $lead->tenant_id = $dealerA->id;
        $lead->save();
        $lead->activities()->create(['activity_type' => 'dibuat', 'notes' => 'Lead masuk dari form']);
        $buyer->favorites()->attach($first->id);
    }

    protected function user(string $name, string $email, string $pw, string $role, ?string $phone = null): User
    {
        $u = User::create(['name' => $name, 'email' => $email, 'password' => $pw, 'phone' => $phone]);
        $u->forceFill(['platform_role' => $role, 'email_verified_at' => now()])->save();

        return $u;
    }

    protected function tenant(string $name, User $owner, string $cityName, string $planCode, string $verification, string $desc, ?array $theme = null): Tenant
    {
        $city = City::where('name', $cityName)->first();
        $t = new Tenant(['name' => $name, 'phone' => $owner->phone, 'whatsapp' => $owner->phone, 'email' => $owner->email, 'city_id' => $city->id, 'description' => $desc]);
        $t->slug = Str::slug($name);
        $t->owner_user_id = $owner->id;
        $t->status = 'active';
        $t->verification_status = $verification;
        if ($theme) {
            $t->settings_json = ['theme' => $theme];
        }
        $t->save();
        $b = new TenantBranch(['name' => 'Cabang Utama', 'address' => 'Jl. Contoh Raya No. 1, '.$cityName, 'city_id' => $city->id,
            'latitude' => $city->latitude, 'longitude' => $city->longitude, 'phone' => $owner->phone, 'is_default' => true]);
        $b->tenant_id = $t->id;
        $b->save();
        TenantMembership::create(['tenant_id' => $t->id, 'user_id' => $owner->id, 'branch_id' => $b->id, 'role' => 'tenant_owner', 'status' => 'active', 'joined_at' => now()]);
        $t->subscriptions()->create(['plan_id' => Plan::where('code', $planCode)->value('id'), 'status' => 'active', 'starts_at' => now(),
            'current_period_start' => now()->startOfMonth(), 'current_period_end' => now()->startOfMonth()->addMonth()]);
        $owner->forceFill(['current_tenant_id' => $t->id])->save();

        return $t;
    }

    protected function unitWithListing(?Tenant $tenant, User $seller, string $modelSlug, string $cond, int $year, string $color, int $km, int $price, ?int $buy, int $seed): Listing
    {
        $model = VehicleModel::where('slug', $modelSlug)->with('brand')->firstOrFail();
        $unit = new VehicleUnit(['model_id' => $model->id, 'condition' => $cond, 'year' => $year, 'color' => $color,
            'odometer_km' => $cond === 'new' ? 0 : $km, 'asking_price' => $price, 'ownership_type' => 'owned',
            'registration_status' => $cond === 'new' ? 'baru' : 'lengkap', 'branch_id' => $tenant?->branches()->first()->id,
            'stock_code' => $tenant ? 'STK-'.str_pad((string) ($seed + 1), 4, '0', STR_PAD_LEFT) : null, 'acquired_at' => now()->subDays(20 + $seed)]);
        $unit->tenant_id = $tenant?->id;
        $unit->seller_user_id = $tenant ? null : $seller->id;
        $unit->status = 'available';
        $unit->purchase_price = $buy;
        $unit->setFrameNumber('MH1DEMO'.str_pad((string) $seed, 10, '0', STR_PAD_LEFT));
        $unit->save();

        if ($tenant && $buy) {
            foreach ([['pembelian', 'Harga beli unit', $buy, 'posted'], ['service', 'Servis rutin & ganti oli', (int) round($price * 0.01), 'posted'],
                ['detailing', 'Detailing & poles', (int) round($price * 0.004), 'posted'], ['sparepart', 'Estimasi ganti ban belakang', (int) round($price * 0.012), 'estimate']] as [$cat, $desc, $amt, $st]) {
                $c = new VehicleCostEntry(['unit_id' => $unit->id, 'category' => $cat, 'description' => $desc, 'amount' => $amt, 'status' => $st, 'entry_date' => now()->subDays(10)->toDateString()]);
                $c->tenant_id = $tenant->id;
                $c->save();
            }
        }

        $folder = ($tenant ? 'tenants/'.$tenant->id : 'sellers/'.$seller->id).'/units/'.$unit->id;
        foreach (['depan', 'kanan', 'kiri', 'belakang'] as $k => $cat) {
            $path = "{$folder}/{$cat}/demo.png";
            Storage::disk('public')->put($path, DemoImage::vehicle($model->category, $model->fullName(), $color, $k, $seed));
            UnitMedia::create(['unit_id' => $unit->id, 'tenant_id' => $unit->tenant_id, 'category' => $cat, 'disk' => 'public', 'path' => $path,
                'mime' => 'image/png', 'size_bytes' => Storage::disk('public')->size($path), 'checksum' => hash('sha256', $path),
                'verification_status' => 'terverifikasi', 'publish_consent' => true]);
        }

        $listing = new Listing(['unit_id' => $unit->id, 'category' => $model->category, 'condition' => $cond,
            'title' => $model->fullName().' '.$year.($cond === 'new' ? ' Baru' : ''),
            'description' => $cond === 'new'
                ? "Unit baru {$model->fullName()} warna {$color}. Harga on the road dapat berbeda per wilayah; hubungi kami untuk penawaran resmi."
                : "{$model->fullName()} tahun {$year}, warna {$color}, kilometer ".number_format($km, 0, ',', '.').". Pajak hidup, dokumen lengkap. Silakan jadwalkan kunjungan untuk cek fisik.",
            'price' => $price, 'negotiable' => $cond !== 'new', 'city_id' => $tenant?->city_id ?? City::where('name', 'Bandung')->value('id')]);
        $listing->tenant_id = $unit->tenant_id;
        $listing->seller_user_id = $seller->id;
        $listing->slug = Str::slug($listing->title).'-'.Str::lower(Str::random(6));
        $listing->status = 'published';
        $listing->published_at = now()->subHours($seed * 5);
        $listing->expires_at = now()->addDays(60);
        $listing->save();

        return $listing;
    }
}
