<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GoldPriceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public float $price,
        public float $changePercent,
        public float $change
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('xauusd-price');
    }

    public function broadcastAs(): string
    {
        return 'price.updated';
    }
}
