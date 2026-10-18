<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Listing;
use App\Models\VehicleUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Smoke test: semua halaman dashboard dealer, akun, dan admin dapat dirender. */
class PanelPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDemo();
        config(['marketplace.weather.enabled' => false]);
    }

    public function test_dealer_pages_render(): void
    {
        $owner = $this->user('dealer@example.com');
        $tenant = $this->tenant('dealer-motor-contoh');
        $unit = VehicleUnit::where('tenant_id', $tenant->id)->first();
        $lead = Lead::where('tenant_id', $tenant->id)->first();

        foreach ([route('dealer.dashboard'), route('dealer.units.index'), route('dealer.units.create'), route('dealer.units.show', $unit),
            route('dealer.units.edit', $unit), route('dealer.listings.index'), route('dealer.leads.index'), route('dealer.leads.show', $lead),
            route('dealer.bookings.index'), route('dealer.members.index'), route('dealer.billing'), route('dealer.settings'), route('dealer.audit')] as $url) {
            $this->actingAs($owner)->get($url)->assertOk();
        }
        $this->actingAs($owner)->get(route('dealer.dashboard'))->assertSee('Cuaca tidak tersedia')->assertSee('Jakarta Selatan');
    }

    public function test_account_pages_render(): void
    {
        $seller = $this->user('penjual@example.com');
        $listing = Listing::where('seller_user_id', $seller->id)->first();
        foreach ([route('account.index'), route('account.favorites'), route('account.listings'), route('account.listings.show', $listing), route('account.leads'), route('sell.create')] as $url) {
            $this->actingAs($seller)->get($url)->assertOk();
        }
        $this->actingAs($this->user('pembeli@example.com'))->get(route('account.listings.show', $listing))->assertNotFound();
    }

    public function test_admin_pages_render_and_are_restricted(): void
    {
        $admin = $this->user('admin@example.com');
        $tenant = $this->tenant('dealer-motor-contoh');
        foreach ([route('admin.dashboard'), route('admin.moderation'), route('admin.reports'), route('admin.tenants.index'), route('admin.tenants.show', $tenant->id),
            route('admin.users.index'), route('admin.catalog'), route('admin.plans'), route('admin.audit')] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
        $this->actingAs($this->user('moderator@example.com'))->get(route('admin.tenants.index'))->assertForbidden();
        $this->actingAs($this->user('dealer@example.com'))->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_onboarding_creates_tenant_with_owner_branch_and_free_plan(): void
    {
        $buyer = $this->user('pembeli@example.com');
        $this->actingAs($buyer)->post(route('dealer.onboarding.store'), [
            'name' => 'Dealer Baru', 'phone' => '081299990000', 'city_id' => 3, 'address' => 'Jl. Contoh No. 2',
        ])->assertRedirect(route('dealer.dashboard'));
        $tenant = $this->tenant('dealer-baru');
        $this->assertSame('unverified', $tenant->verification_status);
        $this->assertSame('tenant_owner', $tenant->memberships()->first()->role);
        $this->assertSame('gratis', $tenant->activeSubscription->plan->code);
        $this->assertSame(1, $tenant->branches()->count());
    }

    public function test_theme_contrast_is_validated(): void
    {
        $owner = $this->user('dealer@example.com');
        $this->actingAs($owner)->put(route('dealer.settings.theme'), ['primary' => '#ffff00', 'accent' => '#000000'])->assertSessionHasErrors('primary');
        $this->actingAs($owner)->put(route('dealer.settings.theme'), ['primary' => '#13795b', 'accent' => '#e0a100'])->assertSessionHasNoErrors();
        $this->assertSame('#13795b', $this->tenant('dealer-motor-contoh')->theme()['primary']);
    }

    public function test_admin_suspension_requires_reason_and_is_audited(): void
    {
        $admin = $this->user('admin@example.com');
        $tenant = $this->tenant('showroom-mobil-contoh');
        $this->actingAs($admin)->post(route('admin.tenants.status', $tenant->id), ['status' => 'suspended'])->assertSessionHasErrors('reason');
        $this->actingAs($admin)->post(route('admin.tenants.status', $tenant->id), ['status' => 'suspended', 'reason' => 'Tunggakan'])->assertRedirect();
        $this->assertSame('suspended', $tenant->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'platform.tenant_status_changed', 'reason' => 'Tunggakan']);
        $this->assertSame(4, VehicleUnit::where('tenant_id', $tenant->id)->count(), 'Data tenant tidak dihapus saat suspend.');
    }
}
