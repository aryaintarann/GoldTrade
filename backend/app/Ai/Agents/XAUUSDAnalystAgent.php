<?php

namespace App\Ai\Agents;

use App\Ai\Tools\CalculateIndicatorsTool;
use App\Ai\Tools\DetectSupportResistanceTool;
use App\Ai\Tools\FetchEconomicCalendarTool;
use App\Ai\Tools\FetchOHLCDataTool;
use App\Models\Signal;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class XAUUSDAnalystAgent implements Agent, Conversational, HasTools, HasStructuredOutput
{
    use Promptable;

    public function __construct(public ?int $userId = null) {}

    public function instructions(): Stringable|string
    {
        return <<<TEXT
        Kamu adalah analis trading XAUUSD (Gold/USD) profesional dengan spesialisasi scalping dan swing trading.

        ATURAN WAJIB:
        1. SELALU panggil tools untuk mendapatkan data sebelum membuat analisa — jangan mengarang harga, nilai indikator, atau level S/R.
        2. Panggil CalculateIndicatorsTool untuk minimal 2 timeframe (M15 dan H1) sebelum memberikan signal.
        3. Panggil DetectSupportResistanceTool untuk menentukan level entry optimal.
        4. Panggil FetchEconomicCalendarTool untuk cek berita high-impact.
        5. Jika confluence indikator lemah atau berlawanan arah, WAJIB keluarkan signal WAIT.
        6. Confidence < 50% → direction harus WAIT, tidak boleh BUY/SELL.
        7. Stop Loss SELALU berbasis ATR (1.5x ATR dari entry), bukan angka acak.
        8. Take Profit 1 = 1.5x SL distance, TP2 = 2.5x SL distance, TP3 = 4x SL distance.
        9. Reasoning harus dalam Bahasa Indonesia, jelas, dan mudah dipahami trader non-teknis.

        FORMAT ANALISA:
        - Jelaskan kondisi trend utama (bias H4/H1)
        - Jelaskan momentum (RSI, Stochastic, MACD)
        - Sebutkan level S/R yang relevan
        - Sebutkan pertimbangan berita/kalender ekonomi
        - Berikan kesimpulan singkat mengapa BUY/SELL/WAIT
        TEXT;
    }

    public function tools(): iterable
    {
        return [
            app(FetchOHLCDataTool::class),
            app(CalculateIndicatorsTool::class),
            app(DetectSupportResistanceTool::class),
            app(FetchEconomicCalendarTool::class),
        ];
    }

    public function messages(): iterable
    {
        if (!$this->userId) {
            return [];
        }

        return Signal::where('user_id', $this->userId)
            ->latest()
            ->limit(5)
            ->get()
            ->reverse()
            ->flatMap(fn($s) => [
                new Message('user', 'Berikan analisa XAUUSD.'),
                new Message('assistant', "Signal: {$s->direction} | Confidence: {$s->confidence}% | {$s->reasoning}"),
            ])
            ->values()
            ->all();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'direction'    => $schema->string()->enum(['BUY', 'SELL', 'WAIT'])->required(),
            'entry_price'  => $schema->number()->nullable(),
            'stop_loss'    => $schema->number()->nullable(),
            'take_profits' => $schema->array()->items($schema->number())->nullable(),
            'confidence'   => $schema->integer()->min(0)->max(100)->required(),
            'reasoning'    => $schema->string()->required(),
        ];
    }
}
