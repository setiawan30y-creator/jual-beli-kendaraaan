<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantBranch extends Model
{
    protected $guarded = ['id', 'tenant_id'];

    protected $casts = ['business_hours_json' => 'array', 'is_default' => 'boolean', 'latitude' => 'float', 'longitude' => 'float'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
