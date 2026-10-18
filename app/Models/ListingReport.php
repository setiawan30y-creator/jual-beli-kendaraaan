<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingReport extends Model
{
    public const REASONS = [
        'penipuan' => 'Indikasi penipuan', 'data_salah' => 'Data/harga tidak sesuai', 'sudah_terjual' => 'Kendaraan sudah terjual',
        'duplikat' => 'Iklan duplikat', 'konten_tidak_pantas' => 'Konten tidak pantas', 'lainnya' => 'Lainnya',
    ];

    protected $guarded = ['id', 'status', 'reviewed_by'];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }
}
