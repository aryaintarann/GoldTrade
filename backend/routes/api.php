<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\SignalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);

    // AI chat streaming endpoint
    Route::post('/chat', [ChatController::class, 'stream'])
        ->middleware('throttle:10,1');

    // Signal journal & performance
    Route::get('/signals', [SignalController::class, 'index']);
    Route::get('/signals/performance', [SignalController::class, 'performance']);

    // Position size calculator
    Route::get('/tools/position-size', function (Request $request) {
        $balance   = (float) $request->get('balance', 10000);
        $riskPct   = (float) $request->get('risk_pct', 1);
        $slPips    = (float) $request->get('sl_pips', 15);
        $pipValue  = 10; // XAUUSD standard lot = $10/pip

        $riskAmount = $balance * ($riskPct / 100);
        $lotSize    = $slPips > 0 ? round($riskAmount / ($slPips * $pipValue), 2) : 0;
        $rrRatio    = $request->get('tp_pips') ? round((float)$request->get('tp_pips') / $slPips, 2) : null;

        return response()->json([
            'balance'     => $balance,
            'risk_pct'    => $riskPct,
            'risk_amount' => $riskAmount,
            'sl_pips'     => $slPips,
            'lot_size'    => $lotSize,
            'rr_ratio'    => $rrRatio,
            'pip_value'   => $pipValue,
        ]);
    });
});
