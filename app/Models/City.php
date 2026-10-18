<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['latitude' => 'float', 'longitude' => 'float'];
}
