<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleCostEntry extends Model
{
    public const CATEGORIES = [
        'pembelian' => 'Harga beli', 'akuisisi' => 'Biaya akuisisi', 'service' => 'Service/perbaikan',
        'sparepart' => 'Sparepart', 'transportasi' => 'Transportasi', 'detailing' => 'Detailing',
        'administrasi' => 'Administrasi/dokumen', 'lainnya' => 'Biaya langsung lain',
    ];

    protected $guarded = ['id', 'tenant_id'];

    protected $casts = ['amount' => 'decimal:2', 'entry_date' => 'date'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(VehicleUnit::class, 'unit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
