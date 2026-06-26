<?php

namespace App\Services;

use App\Models\NewsEvent;
use Illuminate\Support\Carbon;

class EconomicCalendarService
{
    public function getUpcomingHighImpactEvents(int $hours = 24): array
    {
        $events = NewsEvent::where('impact', 'high')
            ->where('event_time', '>=', Carbon::now())
            ->where('event_time', '<=', Carbon::now()->addHours($hours))
            ->where('currency', 'USD')
            ->orderBy('event_time')
            ->get()
            ->toArray();

        if (empty($events)) {
            $events = $this->getMockEvents();
        }

        return $events;
    }

    public function hasHighImpactInNextHours(int $hours = 2): bool
    {
        return NewsEvent::where('impact', 'high')
            ->where('event_time', '>=', Carbon::now())
            ->where('event_time', '<=', Carbon::now()->addHours($hours))
            ->where('currency', 'USD')
            ->exists();
    }

    private function getMockEvents(): array
    {
        return [];
    }
}
