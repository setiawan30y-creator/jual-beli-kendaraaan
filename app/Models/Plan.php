<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['limits_json' => 'array', 'features_json' => 'array', 'price' => 'decimal:2'];

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function limit(string $key): ?int
    {
        $value = $this->limits_json[$key] ?? null;

        return $value === null ? null : (int) $value; // null = unlimited
    }
}
