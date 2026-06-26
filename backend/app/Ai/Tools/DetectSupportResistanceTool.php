<?php

namespace App\Ai\Tools;

use App\Services\OHLCDataService;
use App\Services\SupportResistanceService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Providers\Tools\Request;
use Stringable;

class DetectSupportResistanceTool implements Tool
{
    public function __construct(
        private SupportResistanceService $srService,
        private OHLCDataService $ohlcService
    ) {}

    public function description(): Stringable|string
    {
        return 'Mendeteksi level Support dan Resistance XAUUSD dari swing high/low pada timeframe tertentu. Gunakan untuk menentukan zona entry yang optimal dan menetapkan stop loss.';
    }

    public function handle(Request $request): Stringable|string
    {
        $timeframe = $request->get('timeframe', 'H1');
        $candles   = $this->ohlcService->getCandles($timeframe, 100);
        $result    = $this->srService->detect($candles);

        return json_encode(array_merge(['timeframe' => $timeframe], $result));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'timeframe' => $schema->string()->enum(['M15', 'H1', 'H4'])->required(),
        ];
    }
}
