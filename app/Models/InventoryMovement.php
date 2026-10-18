<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    public const UPDATED_AT = null;

    public const TYPES = [
        'purchase_in' => ['label' => 'Pembelian/terima barang', 'sign' => 1],
        'usage_out' => ['label' => 'Pemakaian untuk unit', 'sign' => -1],
        'usage_return_in' => ['label' => 'Retur pemakaian', 'sign' => 1],
        'adjustment_in' => ['label' => 'Penyesuaian (+)', 'sign' => 1],
        'adjustment_out' => ['label' => 'Penyesuaian (−) / rusak', 'sign' => -1],
    ];

    protected $guarded = ['id'];

    protected $casts = ['qty' => 'decimal:2', 'unit_cost' => 'decimal:2', 'total_cost' => 'decimal:2', 'balance_after' => 'decimal:2', 'created_at' => 'datetime'];

    public function part(): BelongsTo
    {
        return $this->belongsTo(SparePart::class, 'spare_part_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(VehicleUnit::class, 'unit_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signedQty(): float
    {
        return self::TYPES[$this->type]['sign'] * (float) $this->qty;
    }
}
