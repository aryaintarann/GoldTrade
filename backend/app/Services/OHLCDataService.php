<?php

namespace App\Services;

use App\Models\MarketDataCache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OHLCDataService
{
    private const CACHE_TTL_MINUTES = [
        'M1'  => 1,
        'M5'  => 5,
        'M15' => 15,
        'H1'  => 60,
        'H4'  => 240,
    ];

    private const AV_INTERVALS = [
        'M1'  => '1min',
        'M5'  => '5min',
        'M15' => '15min',
        'H1'  => '60min',
        'H4'  => '60min', // AV doesn't have 4h, aggregate from 1h
    ];

    public function getCandles(string $timeframe, int $limit = 100): array
    {
        $cached = $this->getCachedCandles($timeframe);

        if ($cached !== null) {
            return array_slice($cached, -$limit);
        }

        $candles = $this->fetchFromAPI($timeframe);

        if (!empty($candles)) {
            $this->cacheCandles($timeframe, $candles);
        }

        return array_slice($candles ?: $this->getMockCandles($timeframe, $limit), -$limit);
    }

    public function getSpotPrice(): float
    {
        try {
            $key = config('services.alpha_vantage.key');
            if (!$key) {
                return $this->getMockPrice();
            }

            $response = Http::timeout(5)->get('https://www.alphavantage.co/query', [
                'function'     => 'CURRENCY_EXCHANGE_RATE',
                'from_currency' => 'XAU',
                'to_currency'  => 'USD',
                'apikey'       => $key,
            ]);

            $data = $response->json();
            $rate = $data['Realtime Currency Exchange Rate']['5. Exchange Rate'] ?? null;

            return $rate ? (float) $rate : $this->getMockPrice();
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch spot price: ' . $e->getMessage());
            return $this->getMockPrice();
        }
    }

    private function getCachedCandles(string $timeframe): ?array
    {
        $ttl = self::CACHE_TTL_MINUTES[$timeframe] ?? 5;
        $cache = MarketDataCache::where('timeframe', $timeframe)
            ->where('fetched_at', '>=', Carbon::now()->subMinutes($ttl))
            ->first();

        return $cache?->candles;
    }

    private function cacheCandles(string $timeframe, array $candles): void
    {
        MarketDataCache::updateOrCreate(
            ['timeframe' => $timeframe],
            ['candles' => $candles, 'fetched_at' => Carbon::now()]
        );
    }

    private function fetchFromAPI(string $timeframe): array
    {
        try {
            $key = config('services.alpha_vantage.key');
            if (!$key) {
                return [];
            }

            $interval = self::AV_INTERVALS[$timeframe] ?? '5min';
            $response = Http::timeout(10)->get('https://www.alphavantage.co/query', [
                'function'         => 'FX_INTRADAY',
                'from_symbol'      => 'XAU',
                'to_symbol'        => 'USD',
                'interval'         => $interval,
                'outputsize'       => 'compact',
                'apikey'           => $key,
            ]);

            $data    = $response->json();
            $key_ts  = "Time Series FX ({$interval})";
            $series  = $data[$key_ts] ?? [];

            if (empty($series)) {
                return [];
            }

            $candles = [];
            foreach (array_reverse(array_keys($series)) as $time) {
                $bar      = $series[$time];
                $candles[] = [
                    'time'   => $time,
                    'open'   => (float) $bar['1. open'],
                    'high'   => (float) $bar['2. high'],
                    'low'    => (float) $bar['3. low'],
                    'close'  => (float) $bar['4. close'],
                    'volume' => 0,
                ];
            }

            return $candles;
        } catch (\Throwable $e) {
            Log::warning('Alpha Vantage fetch failed: ' . $e->getMessage());
            return [];
        }
    }

    private function getMockCandles(string $timeframe, int $limit = 100): array
    {
        $candles = [];
        $price   = 2380.0;
        $now     = time();
        $step    = match ($timeframe) {
            'M1'  => 60,
            'M5'  => 300,
            'M15' => 900,
            'H1'  => 3600,
            'H4'  => 14400,
            default => 300,
        };

        for ($i = $limit; $i >= 0; $i--) {
            $open    = $price;
            $change  = (rand(-100, 100) / 100) * 2.5;
            $close   = round($open + $change, 2);
            $high    = round(max($open, $close) + abs($change) * 0.5, 2);
            $low     = round(min($open, $close) - abs($change) * 0.5, 2);
            $price   = $close;

            $candles[] = [
                'time'   => date('Y-m-d H:i:s', $now - $i * $step),
                'open'   => $open,
                'high'   => $high,
                'low'    => $low,
                'close'  => $close,
                'volume' => rand(100, 1000),
            ];
        }

        return $candles;
    }

    private function getMockPrice(): float
    {
        return round(2380.0 + (rand(-500, 500) / 100), 2);
    }
}
