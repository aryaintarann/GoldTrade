<?php

namespace App\Ai\Tools;

use App\Services\OHLCDataService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Providers\Tools\Request;
use Stringable;

class FetchOHLCDataTool implements Tool
{
    public function __construct(private OHLCDataService $service) {}

    public function description(): Stringable|string
    {
        return 'Mengambil data candlestick OHLCV (Open, High, Low, Close, Volume) XAUUSD untuk timeframe tertentu. Gunakan sebelum menghitung indikator teknikal.';
    }

    public function handle(Request $request): Stringable|string
    {
        $timeframe = $request->get('timeframe', 'M15');
        $limit     = (int) $request->get('limit', 100);

        $candles = $this->service->getCandles($timeframe, $limit);

        return json_encode([
            'timeframe' => $timeframe,
            'count'     => count($candles),
            'candles'   => array_slice($candles, -20), // kirim 20 terbaru ke AI
            'last_close'=> end($candles)['close'] ?? 0,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'timeframe' => $schema->string()->enum(['M1', 'M5', 'M15', 'H1', 'H4'])->required(),
            'limit'     => $schema->integer()->min(50)->max(200),
        ];
    }
}
