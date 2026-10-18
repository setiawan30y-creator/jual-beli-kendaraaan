<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleModel extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['specifications_json' => 'array'];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class, 'brand_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(VehicleVariant::class, 'model_id');
    }

    public function fullName(): string
    {
        return trim(($this->brand?->name ?? '').' '.$this->name);
    }
}
