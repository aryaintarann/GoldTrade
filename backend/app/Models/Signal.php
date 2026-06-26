<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signal extends Model
{
    protected $fillable = [
        'user_id',
        'direction',
        'entry_price',
        'stop_loss',
        'take_profits',
        'confidence',
        'reasoning',
        'indicators_snapshot',
        'timeframes_used',
        'outcome',
    ];

    protected $casts = [
        'take_profits' => 'array',
        'indicators_snapshot' => 'array',
        'timeframes_used' => 'array',
        'entry_price' => 'decimal:2',
        'stop_loss' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
