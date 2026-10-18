<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\Party;
use App\Models\SparePart;
use App\Models\VehicleUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SparePartStockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDemo();
    }

    protected function unitA(): VehicleUnit
    {
        return VehicleUnit::where('tenant_id', $this->tenant('dealer-motor-contoh')->id)->orderBy('id')->skip(1)->first();
    }

    public function test_pages_render(): void
    {
        $owner = $this->user('dealer@example.com');
        $part = SparePart::where('sku', 'OLI-MTR-08')->first();
        $this->actingAs($owner)->get(route('dealer.parts.index'))->assertOk()->assertSee('Oli mesin motor 0.8L');
        $this->actingAs($owner)->get(route('dealer.parts.index', ['menipis' => 1]))->assertOk();
        $this->actingAs($owner)->get(route('dealer.parts.show', $part))->assertOk()->assertSee('Kartu stok');
        $this->actingAs($owner)->get(route('dealer.units.show', $this->unitA()))->assertOk()->assertSee('Pakai sparepart dari stok');
    }

    public function test_receive_updates_weighted_average_cost(): void
    {
        $owner = $this->user('dealer@example.com');
        $this->actingAs($owner)->post(route('dealer.parts.store'), ['sku' => 'filter-01', 'name' => 'Filter udara', 'uom' => 'pcs'])->assertRedirect();
        $part = SparePart::where('sku', 'FILTER-01')->firstOrFail();
        $supplier = Party::where('name', 'Toko Sparepart Contoh')->first();

        $this->actingAs($owner)->post(route('dealer.parts.receive', $part), ['qty' => 10, 'unit_cost' => 40000, 'party_id' => $supplier->id]);
        $this->actingAs($owner)->post(route('dealer.parts.receive', $part), ['qty' => 10, 'unit_cost' => 50000]);
        $part->refresh();
        $this->assertEquals(20, (float) $part->qty_on_hand);
        $this->assertEquals(45000, (float) $part->avg_cost);
        $this->assertSame(2, $part->movements()->count());
    }

    public function test_use_for_unit_reduces_stock_and_posts_to_hpp(): void
    {
        $owner = $this->user('dealer@example.com');
        $unit = $this->unitA();
        $part = SparePart::where('sku', 'KMP-REM-DPN')->first(); // 10 set @ 65.000
        $hppBefore = $unit->load('costs')->costSummary()['hpp'];

        $this->actingAs($owner)->post(route('dealer.units.parts.use', $unit), [
            'spare_part_id' => $part->id, 'qty' => 2, 'note' => 'Rem depan', 'idempotency_key' => 'k1',
        ])->assertRedirect()->assertSessionHasNoErrors();
        // klik ganda tidak memotong stok dua kali
        $this->actingAs($owner)->post(route('dealer.units.parts.use', $unit), [
            'spare_part_id' => $part->id, 'qty' => 2, 'idempotency_key' => 'k1',
        ]);

        $this->assertEquals(8, (float) $part->fresh()->qty_on_hand);
        $entry = $unit->costs()->whereNotNull('inventory_movement_id')->latest('id')->first();
        $this->assertSame('sparepart', $entry->category);
        $this->assertEquals(130000, (float) $entry->amount);
        $this->assertEquals($hppBefore + 130000, $unit->fresh()->load('costs')->costSummary()['hpp']);

        // stok tidak boleh minus
        $this->actingAs($owner)->post(route('dealer.units.parts.use', $unit), [
            'spare_part_id' => $part->id, 'qty' => 99, 'idempotency_key' => 'k2',
        ])->assertSessionHasErrors('qty');
        $this->assertEquals(8, (float) $part->fresh()->qty_on_hand);
    }

    public function test_reversing_spare_part_cost_returns_stock(): void
    {
        $owner = $this->user('dealer@example.com');
        $unit = $this->unitA();
        $part = SparePart::where('sku', 'BAN-90-90-14')->first(); // 6 pcs
        $hppBefore = $unit->load('costs')->costSummary()['hpp'];
        $this->actingAs($owner)->post(route('dealer.units.parts.use', $unit), ['spare_part_id' => $part->id, 'qty' => 1, 'idempotency_key' => 'r1']);
        $entry = $unit->costs()->whereNotNull('inventory_movement_id')->latest('id')->first();

        $this->actingAs($owner)->post(route('dealer.units.costs.reverse', [$unit, $entry]), ['reason' => 'Batal ganti ban'])->assertRedirect();
        $this->assertEquals(6, (float) $part->fresh()->qty_on_hand);
        $this->assertEquals($hppBefore, $unit->fresh()->load('costs')->costSummary()['hpp']);
        $this->assertDatabaseHas('inventory_movements', ['spare_part_id' => $part->id, 'type' => 'usage_return_in', 'reverses_id' => $entry->inventory_movement_id]);
    }

    public function test_stock_opname_adjusts_with_reason(): void
    {
        $owner = $this->user('dealer@example.com');
        $part = SparePart::where('sku', 'BUSI-STD')->first(); // 4
        $this->actingAs($owner)->post(route('dealer.parts.adjust', $part), ['counted_qty' => 3])->assertSessionHasErrors('reason');
        $this->actingAs($owner)->post(route('dealer.parts.adjust', $part), ['counted_qty' => 3, 'reason' => 'Rusak 1'])->assertRedirect();
        $this->assertEquals(3, (float) $part->fresh()->qty_on_hand);
        $this->assertDatabaseHas('inventory_movements', ['spare_part_id' => $part->id, 'type' => 'adjustment_out', 'qty' => 1]);
        $this->assertTrue($part->fresh()->isLow());
    }

    public function test_tenant_isolation_and_permissions(): void
    {
        $part = SparePart::where('sku', 'AKI-5AH')->first();
        $other = $this->user('showroom@example.com');
        $this->actingAs($other)->get(route('dealer.parts.show', $part))->assertNotFound();
        $this->actingAs($other)->post(route('dealer.parts.receive', $part), ['qty' => 1, 'unit_cost' => 1])->assertNotFound();

        // Dealer lain tidak bisa memakai sparepart dealer A untuk unitnya
        $unitB = VehicleUnit::where('tenant_id', $this->tenant('showroom-mobil-contoh')->id)->first();
        $this->actingAs($other)->post(route('dealer.units.parts.use', $unitB), ['spare_part_id' => $part->id, 'qty' => 1, 'idempotency_key' => 'x'])
            ->assertSessionHasErrors('spare_part_id');

        // Sales tidak punya akses stok
        $this->actingAs($this->user('sales@example.com'))->get(route('dealer.parts.index'))->assertForbidden();
        $this->assertEquals(3, (float) $part->fresh()->qty_on_hand);
        $this->assertSame(1, InventoryMovement::where('spare_part_id', $part->id)->count());
    }
}
