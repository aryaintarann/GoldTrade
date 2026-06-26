<?php

namespace App\Jobs;

use App\Events\GoldPriceUpdated;
use App\Services\OHLCDataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class FetchGoldPriceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(OHLCDataService $service): void
    {
        $price    = $service->getSpotPrice();
        $previous = Cache::get('gold_price_previous', $price);
        $change   = $price - $previous;
        $changePct = $previous > 0 ? ($change / $previous) * 100 : 0;

        Cache::put('gold_price_previous', $price, 3600);

        event(new GoldPriceUpdated(
            price: round($price, 2),
            changePercent: round($changePct, 3),
            change: round($change, 2)
        ));
    }
}
