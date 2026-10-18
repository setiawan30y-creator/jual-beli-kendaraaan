<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Party;
use App\Models\SparePart;
use App\Models\VehicleCostEntry;
use App\Models\VehicleUnit;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Semua perubahan stok sparepart lewat sini: movement ledger + saldo + harga rata-rata
 * diperbarui dalam satu transaksi dengan row lock, sehingga stok, nilai persediaan,
 * dan HPP unit selalu konsisten (Blueprint: "Aturan sinkronisasi dan konsistensi").
 */
class InventoryService
{
    /** Terima barang dari pemasok → stok masuk, harga rata-rata tertimbang diperbarui. */
    public function receive(SparePart $part, float $qty, float $unitCost, ?Party $supplier, ?string $ref, ?string $note): InventoryMovement
    {
        return DB::transaction(function () use ($part, $qty, $unitCost, $supplier, $ref, $note) {
            $p = SparePart::whereKey($part->id)->lockForUpdate()->first();
            $onHand = (float) $p->qty_on_hand;
            $newQty = $onHand + $qty;
            $p->avg_cost = $onHand > 0 ? round((($onHand * (float) $p->avg_cost) + ($qty * $unitCost)) / $newQty, 2) : $unitCost;
            $p->qty_on_hand = $newQty;
            $p->save();

            $m = $this->movement($p, 'purchase_in', $qty, $unitCost, ['party_id' => $supplier?->id, 'reference_no' => $ref, 'note' => $note]);
            Audit::log('stock.received', $m, null, ['sku' => $p->sku, 'qty' => $qty, 'unit_cost' => $unitCost]);

            return $m;
        });
    }

    /**
     * Pakai sparepart untuk perbaikan unit → stok keluar dengan harga rata-rata,
     * dan nilai yang sama otomatis diposting ke kartu HPP unit.
     */
    public function useForUnit(SparePart $part, VehicleUnit $unit, float $qty, ?string $note): VehicleCostEntry
    {
        return DB::transaction(function () use ($part, $unit, $qty, $note) {
            $p = SparePart::whereKey($part->id)->lockForUpdate()->first();
            if ($qty > (float) $p->qty_on_hand) {
                throw ValidationException::withMessages(['qty' => "Stok {$p->name} tidak cukup (tersedia ".SparePart::qty($p->qty_on_hand)." {$p->uom})."]);
            }
            $cost = (float) $p->avg_cost;
            $p->qty_on_hand = (float) $p->qty_on_hand - $qty;
            $p->save();

            $m = $this->movement($p, 'usage_out', $qty, $cost, ['unit_id' => $unit->id, 'note' => $note]);

            $entry = new VehicleCostEntry([
                'unit_id' => $unit->id, 'category' => 'sparepart',
                'description' => $p->name.' ('.$p->sku.') × '.SparePart::qty($qty).' '.$p->uom.($note ? ' — '.$note : ''),
                'amount' => round($qty * $cost, 2), 'status' => 'posted', 'entry_date' => now()->toDateString(),
                'reference_no' => 'MV-'.$m->id, 'created_by' => auth()->id(), 'inventory_movement_id' => $m->id,
            ]);
            $entry->tenant_id = $unit->tenant_id;
            $entry->save();

            Audit::log('stock.used_for_unit', $m, null, ['sku' => $p->sku, 'qty' => $qty, 'unit_id' => $unit->id, 'amount' => $entry->amount]);

            return $entry;
        });
    }

    /** Dipanggil saat entri biaya sparepart dibalik: barang kembali ke stok dengan biaya yang sama. */
    public function returnUsage(VehicleCostEntry $entry, string $reason): ?InventoryMovement
    {
        if (! $entry->inventory_movement_id) {
            return null;
        }
        $orig = InventoryMovement::find($entry->inventory_movement_id);
        if (! $orig || $orig->type !== 'usage_out' || InventoryMovement::where('reverses_id', $orig->id)->exists()) {
            return null;
        }
        $p = SparePart::whereKey($orig->spare_part_id)->lockForUpdate()->first();
        $onHand = (float) $p->qty_on_hand;
        $qty = (float) $orig->qty;
        $newQty = $onHand + $qty;
        $p->avg_cost = $onHand > 0 ? round((($onHand * (float) $p->avg_cost) + ($qty * (float) $orig->unit_cost)) / $newQty, 2) : (float) $orig->unit_cost;
        $p->qty_on_hand = $newQty;
        $p->save();

        $m = $this->movement($p, 'usage_return_in', $qty, (float) $orig->unit_cost, [
            'unit_id' => $orig->unit_id, 'reverses_id' => $orig->id, 'note' => 'Retur: '.$reason,
        ]);
        Audit::log('stock.usage_returned', $m, null, ['sku' => $p->sku, 'qty' => $qty], $reason);

        return $m;
    }

    /** Stok opname: sesuaikan saldo sistem ke hasil hitung fisik, wajib alasan. */
    public function adjust(SparePart $part, float $countedQty, string $reason): ?InventoryMovement
    {
        return DB::transaction(function () use ($part, $countedQty, $reason) {
            $p = SparePart::whereKey($part->id)->lockForUpdate()->first();
            $diff = round($countedQty - (float) $p->qty_on_hand, 2);
            if ($diff == 0.0) {
                return null;
            }
            $p->qty_on_hand = $countedQty;
            $p->save();
            $m = $this->movement($p, $diff > 0 ? 'adjustment_in' : 'adjustment_out', abs($diff), (float) $p->avg_cost, ['note' => $reason]);
            Audit::log('stock.adjusted', $m, null, ['sku' => $p->sku, 'diff' => $diff], $reason);

            return $m;
        });
    }

    protected function movement(SparePart $p, string $type, float $qty, float $unitCost, array $extra = []): InventoryMovement
    {
        return InventoryMovement::create(array_merge([
            'tenant_id' => $p->tenant_id, 'spare_part_id' => $p->id, 'type' => $type,
            'qty' => $qty, 'unit_cost' => $unitCost, 'total_cost' => round($qty * $unitCost, 2),
            'balance_after' => $p->qty_on_hand, 'created_by' => auth()->id(),
        ], $extra));
    }
}
