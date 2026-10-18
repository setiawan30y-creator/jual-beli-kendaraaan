<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    public const STATUSES = [
        'baru' => 'Baru', 'dihubungi' => 'Dihubungi', 'negosiasi' => 'Negosiasi',
        'booking' => 'Booking', 'menang' => 'Menang/Terjual', 'tidak_lanjut' => 'Tidak lanjut',
    ];

    protected $guarded = ['id', 'tenant_id'];

    protected $casts = ['next_follow_up_at' => 'datetime', 'contact_consent' => 'boolean'];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest('occurred_at');
    }
}
