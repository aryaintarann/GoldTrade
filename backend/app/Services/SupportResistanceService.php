<?php

namespace App\Services;

class SupportResistanceService
{
    public function detect(array $candles, int $lookback = 50): array
    {
        $candles = array_slice($candles, -$lookback);
        $highs   = array_column($candles, 'high');
        $lows    = array_column($candles, 'low');

        $resistances = $this->findSwingHighs($highs);
        $supports    = $this->findSwingLows($lows);

        $current = end($candles)['close'] ?? 0;

        return [
            'resistances'       => array_values(array_slice(arsort($resistances) ? $resistances : $resistances, 0, 3)),
            'supports'          => array_values(array_slice(asort($supports) ? $supports : $supports, -3)),
            'nearest_resistance'=> $this->nearestAbove($resistances, $current),
            'nearest_support'   => $this->nearestBelow($supports, $current),
        ];
    }

    private function findSwingHighs(array $highs): array
    {
        $pivots = [];
        $n = count($highs);
        for ($i = 2; $i < $n - 2; $i++) {
            if ($highs[$i] > $highs[$i-1] && $highs[$i] > $highs[$i-2]
                && $highs[$i] > $highs[$i+1] && $highs[$i] > $highs[$i+2]) {
                $pivots[] = round($highs[$i], 2);
            }
        }
        return array_unique($pivots);
    }

    private function findSwingLows(array $lows): array
    {
        $pivots = [];
        $n = count($lows);
        for ($i = 2; $i < $n - 2; $i++) {
            if ($lows[$i] < $lows[$i-1] && $lows[$i] < $lows[$i-2]
                && $lows[$i] < $lows[$i+1] && $lows[$i] < $lows[$i+2]) {
                $pivots[] = round($lows[$i], 2);
            }
        }
        return array_unique($pivots);
    }

    private function nearestAbove(array $levels, float $price): ?float
    {
        $above = array_filter($levels, fn($l) => $l > $price);
        return empty($above) ? null : min($above);
    }

    private function nearestBelow(array $levels, float $price): ?float
    {
        $below = array_filter($levels, fn($l) => $l < $price);
        return empty($below) ? null : max($below);
    }
}
