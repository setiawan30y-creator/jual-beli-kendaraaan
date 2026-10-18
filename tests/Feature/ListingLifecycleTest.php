<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\Plan;
use App\Models\VehicleModel;
use App\Models\VehicleUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ListingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDemo();
    }

    public function test_private_seller_flow_from_draft_to_published(): void
    {
        $buyer = $this->user('pembeli@example.com');
        $model = VehicleModel::where('slug', 'yamaha-nmax-155')->first();

        $this->actingAs($buyer)->post(route('sell.store'), [
            'model_id' => $model->id, 'condition' => 'used', 'year' => 2021, 'odometer_km' => 20000, 'price' => 22000000,
            'city_id' => 1, 'title' => 'NMAX 2021 mulus', 'frame_number' => 'MH3ABC1234567890',
            'photos' => ['depan' => UploadedFile::fake()->image('d.jpg', 800, 600)], 'publish_consent' => 1,
        ])->assertRedirect();

        $listing = Listing::where('title', 'NMAX 2021 mulus')->firstOrFail();
        $this->assertSame('draft', $listing->status);
        $this->assertNull($listing->tenant_id);
        $this->assertNotSame('MH3ABC1234567890', $listing->unit->frame_number_encrypted, 'Nomor rangka harus terenkripsi.');
        $this->assertSame('MH3ABC1234567890', $listing->unit->frameNumber());

        $this->actingAs($buyer)->get(route('listings.show', $listing))->assertOk()->assertSee('Pratinjau'); // pemilik melihat pratinjau
        $this->actingAs($this->user('showroom@example.com'))->get(route('listings.show', $listing))->assertNotFound();
        $this->actingAs($buyer)->post(route('account.listings.submit', $listing))->assertRedirect();
        $this->assertSame('pending_review', $listing->fresh()->status);

        // Pengguna biasa tidak bisa memoderasi
        $this->actingAs($buyer)->post(route('admin.moderation.approve', $listing))->assertForbidden();

        $mod = $this->user('moderator@example.com');
        $this->actingAs($mod)->get(route('admin.moderation.show', $listing))->assertOk();
        $this->actingAs($mod)->post(route('admin.moderation.approve', $listing))->assertRedirect();
        $listing->refresh();
        $this->assertSame('published', $listing->status);
        $this->assertNotNull($listing->expires_at);
        $this->app['auth']->forgetGuards();
        $this->get(route('listings.show', $listing))->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'listing.status_changed', 'resource_id' => $listing->id]);
    }

    public function test_reject_requires_reason_and_is_shown_to_owner(): void
    {
        $listing = Listing::where('status', 'pending_review')->first();
        $mod = $this->user('moderator@example.com');
        $this->actingAs($mod)->post(route('admin.moderation.reject', $listing), [])->assertSessionHasErrors('reason');
        $this->actingAs($mod)->post(route('admin.moderation.reject', $listing), ['reason' => 'Foto tidak jelas'])->assertRedirect();
        $this->assertSame('rejected', $listing->fresh()->status);
        $this->actingAs($listing->seller)->get(route('account.listings.show', $listing))->assertOk()->assertSee('Foto tidak jelas');
    }

    public function test_cannot_submit_without_public_photo_or_when_unit_not_available(): void
    {
        $owner = $this->user('dealer@example.com');
        $tenant = $this->tenant('dealer-motor-contoh');
        $this->actingAs($owner)->post(route('dealer.units.store'), [
            'model_id' => VehicleModel::where('slug', 'honda-beat')->value('id'), 'condition' => 'used', 'year' => 2022,
            'ownership_type' => 'owned', 'asking_price' => 15000000, 'purchase_price' => 12000000,
        ])->assertRedirect();
        $unit = VehicleUnit::where('tenant_id', $tenant->id)->latest('id')->first();
        $this->assertSame('inspection', $unit->status);
        $this->assertSame(12000000.0, $unit->costSummary()['hpp'], 'Harga beli otomatis diposting ke HPP.');

        $this->actingAs($owner)->post(route('dealer.listings.store', $unit), ['title' => 'Beat 2022', 'price' => 15000000])->assertRedirect();
        $listing = $unit->listing()->first();
        $this->actingAs($owner)->post(route('dealer.listings.submit', $listing))->assertSessionHasErrors('media');

        $this->actingAs($owner)->post(route('media.store', $unit), ['category' => 'depan', 'file' => UploadedFile::fake()->image('d.jpg'), 'publish_consent' => 1]);
        $this->actingAs($owner)->post(route('dealer.listings.submit', $listing))->assertSessionHasErrors('unit');

        $this->actingAs($owner)->post(route('dealer.units.status', $unit), ['status' => 'available'])->assertRedirect();
        $this->actingAs($owner)->post(route('dealer.listings.submit', $listing))->assertSessionHasNoErrors();
        $this->assertSame('pending_review', $listing->fresh()->status);
    }

    public function test_plan_listing_limit_is_enforced_in_backend(): void
    {
        $tenant = $this->tenant('dealer-motor-contoh');
        $plan = $tenant->activeSubscription->plan;
        $plan->update(['limits_json' => ['listing_limit' => Listing::where('tenant_id', $tenant->id)->whereIn('status', ['pending_review', 'published', 'reserved'])->count(), 'staff_limit' => 10, 'branch_limit' => 3]]);

        $owner = $this->user('dealer@example.com');
        $unit = VehicleUnit::where('tenant_id', $tenant->id)->first();
        $listing = $unit->listing;
        $this->actingAs($owner)->post(route('dealer.listings.archive', $listing));
        // arsip mengosongkan satu slot → pengajuan iklan baru untuk unit lain masih masuk kuota
        $this->assertSame('archived', $listing->fresh()->status);

        $plan->update(['limits_json' => ['listing_limit' => 0, 'staff_limit' => 10, 'branch_limit' => 3]]);
        $this->actingAs($owner)->post(route('dealer.listings.store', $unit), ['title' => 'Ulang', 'price' => 20000000]);
        $new = Listing::where('unit_id', $unit->id)->where('status', 'draft')->firstOrFail();
        $this->actingAs($owner)->post(route('dealer.listings.submit', $new))->assertSessionHasErrors('plan');
    }

    public function test_invalid_unit_status_transition_is_rejected_and_sold_syncs_listing(): void
    {
        $owner = $this->user('dealer@example.com');
        $listing = Listing::where('tenant_id', $this->tenant('dealer-motor-contoh')->id)->where('status', 'published')->first();
        $unit = $listing->unit;

        $this->actingAs($owner)->post(route('dealer.units.status', $unit), ['status' => 'sold'])->assertRedirect();
        $this->assertSame('sold', $unit->fresh()->status);
        $this->assertSame('sold', $listing->fresh()->status);
        $this->actingAs($owner)->post(route('dealer.units.status', $unit), ['status' => 'available'])->assertStatus(422);
    }

    public function test_cost_reversal_keeps_history_and_idempotency_blocks_duplicates(): void
    {
        $owner = $this->user('dealer@example.com');
        $unit = VehicleUnit::where('tenant_id', $this->tenant('dealer-motor-contoh')->id)->first();
        $before = $unit->load('costs')->costSummary()['hpp'];

        $payload = ['category' => 'service', 'description' => 'Ganti kampas rem', 'amount' => 350000, 'status' => 'posted', 'entry_date' => now()->toDateString(), 'idempotency_key' => 'abc-123'];
        $this->actingAs($owner)->post(route('dealer.units.costs.store', $unit), $payload);
        $this->actingAs($owner)->post(route('dealer.units.costs.store', $unit), $payload);
        $this->assertSame(1, $unit->costs()->where('description', 'Ganti kampas rem')->count());
        $this->assertSame($before + 350000, $unit->fresh()->load('costs')->costSummary()['hpp']);

        $cost = $unit->costs()->where('description', 'Ganti kampas rem')->first();
        $this->actingAs($owner)->post(route('dealer.units.costs.reverse', [$unit, $cost]), ['reason' => 'Salah input'])->assertRedirect();
        $this->assertSame('reversed', $cost->fresh()->status);
        $this->assertDatabaseHas('vehicle_cost_entries', ['reverses_id' => $cost->id, 'amount' => -350000]);
        $this->assertSame($before, $unit->fresh()->load('costs')->costSummary()['hpp']);
    }

    public function test_private_seller_listing_limit(): void
    {
        config(['marketplace.private_seller_listing_limit' => 1]);
        $seller = $this->user('penjual@example.com');
        $draft = Listing::where('seller_user_id', $seller->id)->where('status', 'pending_review')->first();
        $draft->status = 'draft';
        $draft->save();
        $this->actingAs($seller)->post(route('account.listings.submit', $draft))->assertSessionHasErrors('plan');
    }
}
