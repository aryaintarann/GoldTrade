<?php

namespace App\Jobs;

use App\Models\Signal;
use App\Services\OHLCDataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateSignalOutcomesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(OHLCDataService $service): void
    {
        $currentPrice = $service->getSpotPrice();

        Signal::where('outcome', 'pending')
            ->whereNotNull('entry_price')
            ->whereNotNull('stop_loss')
            ->chunk(50, function ($signals) use ($currentPrice) {
                foreach ($signals as $signal) {
                    $this->checkOutcome($signal, $currentPrice);
                }
            });
    }

    private function checkOutcome(Signal $signal, float $currentPrice): void
    {
        $tps = $signal->take_profits ?? [];
        $sl  = (float) $signal->stop_loss;

        if ($signal->direction === 'BUY') {
            if ($currentPrice <= $sl) {
                $signal->update(['outcome' => 'sl_hit']);
                return;
            }
            if (!empty($tps) && $currentPrice >= (float) $tps[0]) {
                $signal->update(['outcome' => 'tp_hit']);
            }
        } elseif ($signal->direction === 'SELL') {
            if ($currentPrice >= $sl) {
                $signal->update(['outcome' => 'sl_hit']);
                return;
            }
            if (!empty($tps) && $currentPrice <= (float) $tps[0]) {
                $signal->update(['outcome' => 'tp_hit']);
            }
        }

        // Expire signals older than 24 hours
        if ($signal->created_at->diffInHours(now()) >= 24) {
            $signal->update(['outcome' => 'expired']);
        }
    }
}
