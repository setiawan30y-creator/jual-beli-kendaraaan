<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\UnitMedia;
use App\Models\VehicleUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Blueprint §21: Tenant A tidak dapat membaca atau mengubah resource Tenant B, termasuk lewat ID tebakan. */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDemo();
    }

    public function test_dealer_cannot_access_other_tenant_unit(): void
    {
        $ownerA = $this->user('dealer@example.com');
        $unitB = VehicleUnit::where('tenant_id', $this->tenant('showroom-mobil-contoh')->id)->first();

        $this->actingAs($ownerA)->get(route('dealer.units.show', $unitB))->assertNotFound();
        $this->actingAs($ownerA)->get(route('dealer.units.edit', $unitB))->assertNotFound();
        $this->actingAs($ownerA)->put(route('dealer.units.update', $unitB), ['asking_price' => 1])->assertNotFound();
        $this->actingAs($ownerA)->post(route('dealer.units.status', $unitB), ['status' => 'withdrawn'])->assertNotFound();
        $this->actingAs($ownerA)->post(route('dealer.units.costs.store', $unitB), [])->assertNotFound();
        $this->assertSame('available', $unitB->fresh()->status);
    }

    public function test_dealer_cannot_access_other_tenant_lead_or_media(): void
    {
        $ownerB = $this->user('showroom@example.com');
        $leadA = Lead::where('tenant_id', $this->tenant('dealer-motor-contoh')->id)->first();
        $mediaA = UnitMedia::where('tenant_id', $this->tenant('dealer-motor-contoh')->id)->first();

        $this->actingAs($ownerB)->get(route('dealer.leads.show', $leadA))->assertNotFound();
        $this->actingAs($ownerB)->patch(route('dealer.leads.update', $leadA), ['status' => 'tidak_lanjut'])->assertNotFound();
        $this->actingAs($ownerB)->post(route('dealer.media.verify', $mediaA), ['verification_status' => 'perlu_perbaikan'])->assertNotFound();
        $this->actingAs($ownerB)->delete(route('media.destroy', $mediaA))->assertNotFound();
        $this->assertSame('baru', $leadA->fresh()->status);
    }

    public function test_dealer_cannot_switch_into_tenant_without_membership(): void
    {
        $ownerA = $this->user('dealer@example.com');
        $this->actingAs($ownerA)->post(route('dealer.switch', $this->tenant('showroom-mobil-contoh')->id))->assertNotFound();
    }

    public function test_api_inventory_is_scoped_to_member_tenant(): void
    {
        $ownerA = $this->user('dealer@example.com');
        $tenantA = $this->tenant('dealer-motor-contoh');
        $res = $this->actingAs($ownerA, 'sanctum')->getJson('/api/v1/dealer/inventory?per_page=50')->assertOk();
        $ids = collect($res->json('data'))->pluck('id');
        $this->assertNotEmpty($ids);
        $this->assertEqualsCanonicalizing(VehicleUnit::where('tenant_id', $tenantA->id)->pluck('id')->all(), $ids->all());

        $unitB = VehicleUnit::where('tenant_id', $this->tenant('showroom-mobil-contoh')->id)->first();
        $this->actingAs($ownerA, 'sanctum')->patchJson('/api/v1/dealer/inventory/'.$unitB->id, ['asking_price' => 150000])->assertNotFound();
    }

    public function test_sales_role_sees_only_assigned_leads_and_no_cost_data(): void
    {
        $sales = $this->user('sales@example.com');
        $tenantA = $this->tenant('dealer-motor-contoh');
        $unassigned = Lead::where('tenant_id', $tenantA->id)->first()->replicate(['assigned_user_id']);
        $unassigned->assigned_user_id = null;
        $unassigned->save();

        $this->actingAs($sales)->get(route('dealer.leads.show', $unassigned))->assertNotFound();
        $res = $this->actingAs($sales, 'sanctum')->getJson('/api/v1/dealer/leads')->assertOk();
        $this->assertTrue(collect($res->json('data'))->every(fn ($l) => $l['assigned_user_id'] === $sales->id));

        $inv = $this->actingAs($sales, 'sanctum')->getJson('/api/v1/dealer/inventory')->assertOk();
        $this->assertArrayNotHasKey('purchase_price', $inv->json('data.0'));
        $this->actingAs($sales)->get(route('dealer.units.create'))->assertForbidden();
        $this->actingAs($sales)->get(route('dealer.settings'))->assertForbidden();
    }

    public function test_user_without_membership_is_sent_to_onboarding(): void
    {
        $this->actingAs($this->user('pembeli@example.com'))->get('/dasbor')->assertRedirect(route('dealer.onboarding'));
        $this->actingAs($this->user('pembeli@example.com'), 'sanctum')->getJson('/api/v1/dealer/inventory')->assertForbidden();
    }

    public function test_suspended_tenant_is_limited(): void
    {
        $tenant = $this->tenant('dealer-motor-contoh');
        $tenant->status = 'suspended';
        $tenant->save();
        $owner = $this->user('dealer@example.com');

        $this->actingAs($owner)->get(route('dealer.units.index'))->assertRedirect(route('dealer.suspended'));
        $this->actingAs($owner)->get(route('dealer.billing'))->assertOk();
        $this->get(route('dealers.show', $tenant))->assertNotFound();
        $this->get('/kendaraan')->assertDontSee('Honda Vario 160 2023');
    }
}
