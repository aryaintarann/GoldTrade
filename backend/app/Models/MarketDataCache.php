<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketDataCache extends Model
{
    public $timestamps = false;

    protected $table = 'market_data_cache';

    protected $fillable = ['timeframe', 'candles', 'fetched_at'];

    protected $casts = [
        'candles' => 'array',
        'fetched_at' => 'datetime',
    ];
}
