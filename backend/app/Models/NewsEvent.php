<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['title', 'impact', 'currency', 'event_time'];

    protected $casts = [
        'event_time' => 'datetime',
    ];
}
