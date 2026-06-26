<?php

namespace App\Ai\Tools;

use App\Services\OHLCDataService;
use App\Services\TechnicalIndicatorService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Providers\Tools\Request;
use Stringable;

class CalculateIndicatorsTool implements Tool
{
    public function __construct(
        private TechnicalIndicatorService $indicatorService,
        private OHLCDataService $ohlcService
    ) {}

    public function description(): Stringable|string
    {
        return 'Menghitung RSI, MACD, EMA (9/21/50/200), Bollinger Bands, ATR, dan Stochastic untuk timeframe tertentu pada XAUUSD. Selalu panggil tool ini sebelum membuat signal.';
    }

    public function handle(Request $request): Stringable|string
    {
        $timeframe = $request->get('timeframe', 'M15');
        $candles   = $this->ohlcService->getCandles($timeframe, 200);
        $result    = $this->indicatorService->calculate($timeframe, $candles);

        return json_encode($result);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'timeframe' => $schema->string()->enum(['M1', 'M5', 'M15', 'H1', 'H4'])->required(),
        ];
    }
}
