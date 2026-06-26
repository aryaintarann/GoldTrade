<?php

namespace App\Ai\Tools;

use App\Services\EconomicCalendarService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Providers\Tools\Request;
use Stringable;

class FetchEconomicCalendarTool implements Tool
{
    public function __construct(private EconomicCalendarService $service) {}

    public function description(): Stringable|string
    {
        return 'Mengambil daftar event ekonomi high-impact (NFP, FOMC, CPI, dll) yang akan datang dalam beberapa jam ke depan. Jika ada event high-impact dalam 2 jam, rekomendasikan WAIT.';
    }

    public function handle(Request $request): Stringable|string
    {
        $hours  = (int) $request->get('hours', 4);
        $events = $this->service->getUpcomingHighImpactEvents($hours);
        $hasRisk = $this->service->hasHighImpactInNextHours(2);

        return json_encode([
            'has_high_impact_risk' => $hasRisk,
            'events'               => $events,
            'checked_hours_ahead'  => $hours,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'hours' => $schema->integer()->min(1)->max(24),
        ];
    }
}
