<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SparePart extends Model
{
    public const CATEGORIES = [
        'oli' => 'Oli & cairan', 'ban' => 'Ban & velg', 'rem' => 'Rem', 'kelistrikan' => 'Kelistrikan & aki',
        'mesin' => 'Mesin', 'kaki' => 'Kaki-kaki', 'body' => 'Body & kaca', 'aksesori' => 'Aksesori', 'lainnya' => 'Lainnya',
    ];

    protected $guarded = ['id', 'tenant_id', 'qty_on_hand', 'avg_cost'];

    protected $casts = ['min_stock' => 'decimal:2', 'qty_on_hand' => 'decimal:2', 'avg_cost' => 'decimal:2'];

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function isLow(): bool
    {
        return (float) $this->qty_on_hand <= (float) $this->min_stock;
    }

    public function stockValue(): float
    {
        return round((float) $this->qty_on_hand * (float) $this->avg_cost, 2);
    }

    public static function qty(float|string|null $v): string
    {
        $v = (float) $v;

        return floor($v) == $v ? number_format($v, 0, ',', '.') : number_format($v, 2, ',', '.');
    }
}
