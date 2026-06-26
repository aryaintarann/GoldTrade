<?php

namespace App\Http\Controllers;

use App\Models\Signal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SignalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $signals = Signal::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json($signals);
    }

    public function performance(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $signals = Signal::where('user_id', $userId)
            ->whereIn('outcome', ['tp_hit', 'sl_hit'])
            ->get();

        $total    = $signals->count();
        $tpHits   = $signals->where('outcome', 'tp_hit')->count();
        $slHits   = $signals->where('outcome', 'sl_hit')->count();
        $winRate  = $total > 0 ? round(($tpHits / $total) * 100, 1) : 0;

        $byDirection = $signals->groupBy('direction')->map(function ($group) {
            $total   = $group->count();
            $tpHits  = $group->where('outcome', 'tp_hit')->count();
            return [
                'total'    => $total,
                'win_rate' => $total > 0 ? round(($tpHits / $total) * 100, 1) : 0,
            ];
        });

        $byConfidence = $this->groupByConfidenceRange($signals);

        return response()->json([
            'total'          => $total,
            'tp_hits'        => $tpHits,
            'sl_hits'        => $slHits,
            'win_rate'       => $winRate,
            'by_direction'   => $byDirection,
            'by_confidence'  => $byConfidence,
        ]);
    }

    private function groupByConfidenceRange($signals): array
    {
        $ranges = [
            '50-60' => [50, 60],
            '61-80' => [61, 80],
            '81-100'=> [81, 100],
        ];

        $result = [];
        foreach ($ranges as $label => [$min, $max]) {
            $group    = $signals->filter(fn($s) => $s->confidence >= $min && $s->confidence <= $max);
            $total    = $group->count();
            $tpHits   = $group->where('outcome', 'tp_hit')->count();
            $result[] = [
                'range'    => $label,
                'total'    => $total,
                'win_rate' => $total > 0 ? round(($tpHits / $total) * 100, 1) : 0,
            ];
        }

        return $result;
    }
}
