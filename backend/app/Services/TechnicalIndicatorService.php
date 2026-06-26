<?php

namespace App\Services;

class TechnicalIndicatorService
{
    public function calculate(string $timeframe, array $candles): array
    {
        if (count($candles) < 30) {
            return $this->emptyResult();
        }

        $closes = array_column($candles, 'close');
        $highs  = array_column($candles, 'high');
        $lows   = array_column($candles, 'low');

        return [
            'timeframe'  => $timeframe,
            'rsi'        => $this->calculateRSI($closes, 14),
            'macd'       => $this->calculateMACD($closes, 12, 26, 9),
            'ema'        => $this->calculateEMAs($closes),
            'bb'         => $this->calculateBollingerBands($closes, 20, 2),
            'atr'        => $this->calculateATR($highs, $lows, $closes, 14),
            'stochastic' => $this->calculateStochastic($highs, $lows, $closes, 14, 3),
        ];
    }

    private function calculateRSI(array $closes, int $period = 14): float
    {
        $n = count($closes);
        if ($n <= $period) {
            return 50.0;
        }

        $gains = 0.0;
        $losses = 0.0;

        for ($i = 1; $i <= $period; $i++) {
            $diff = $closes[$n - $period + $i - 1] - $closes[$n - $period + $i - 2];
            if ($diff >= 0) {
                $gains += $diff;
            } else {
                $losses += abs($diff);
            }
        }

        $avgGain = $gains / $period;
        $avgLoss = $losses / $period;

        if ($avgLoss == 0) {
            return 100.0;
        }

        $rs  = $avgGain / $avgLoss;
        return round(100 - (100 / (1 + $rs)), 2);
    }

    private function ema(array $values, int $period): array
    {
        $k      = 2 / ($period + 1);
        $ema    = [];
        $ema[0] = $values[0];

        for ($i = 1; $i < count($values); $i++) {
            $ema[$i] = $values[$i] * $k + $ema[$i - 1] * (1 - $k);
        }

        return $ema;
    }

    private function calculateEMAs(array $closes): array
    {
        $periods = [9, 21, 50, 200];
        $result  = [];

        foreach ($periods as $period) {
            if (count($closes) >= $period) {
                $emaArr = $this->ema($closes, $period);
                $result["ema{$period}"] = round(end($emaArr), 2);
            }
        }

        return $result;
    }

    private function calculateMACD(array $closes, int $fast = 12, int $slow = 26, int $signal = 9): array
    {
        if (count($closes) < $slow + $signal) {
            return ['macd' => 0, 'signal' => 0, 'histogram' => 0];
        }

        $emaFast      = $this->ema($closes, $fast);
        $emaSlow      = $this->ema($closes, $slow);
        $macdLine     = [];

        for ($i = 0; $i < count($closes); $i++) {
            $macdLine[] = ($emaFast[$i] ?? 0) - ($emaSlow[$i] ?? 0);
        }

        $signalLine = $this->ema($macdLine, $signal);
        $macdVal    = end($macdLine);
        $signalVal  = end($signalLine);

        return [
            'macd'      => round($macdVal, 4),
            'signal'    => round($signalVal, 4),
            'histogram' => round($macdVal - $signalVal, 4),
        ];
    }

    private function calculateBollingerBands(array $closes, int $period = 20, float $stdDev = 2.0): array
    {
        $n = count($closes);
        if ($n < $period) {
            $last = end($closes);
            return ['upper' => $last, 'middle' => $last, 'lower' => $last];
        }

        $slice  = array_slice($closes, -$period);
        $sma    = array_sum($slice) / $period;
        $variance = 0;

        foreach ($slice as $val) {
            $variance += pow($val - $sma, 2);
        }

        $std = sqrt($variance / $period);

        return [
            'upper'  => round($sma + $stdDev * $std, 2),
            'middle' => round($sma, 2),
            'lower'  => round($sma - $stdDev * $std, 2),
        ];
    }

    private function calculateATR(array $highs, array $lows, array $closes, int $period = 14): float
    {
        $n = count($closes);
        if ($n < $period + 1) {
            return round($highs[0] - $lows[0], 2);
        }

        $trs = [];
        for ($i = 1; $i < $n; $i++) {
            $trs[] = max(
                $highs[$i] - $lows[$i],
                abs($highs[$i] - $closes[$i - 1]),
                abs($lows[$i] - $closes[$i - 1])
            );
        }

        $recentTrs = array_slice($trs, -$period);
        return round(array_sum($recentTrs) / count($recentTrs), 2);
    }

    private function calculateStochastic(array $highs, array $lows, array $closes, int $kPeriod = 14, int $dPeriod = 3): array
    {
        $n = count($closes);
        if ($n < $kPeriod) {
            return ['k' => 50.0, 'd' => 50.0];
        }

        $kValues = [];
        for ($i = $kPeriod - 1; $i < $n; $i++) {
            $sliceH = array_slice($highs, $i - $kPeriod + 1, $kPeriod);
            $sliceL = array_slice($lows, $i - $kPeriod + 1, $kPeriod);
            $highMax = max($sliceH);
            $lowMin  = min($sliceL);
            $range   = $highMax - $lowMin;
            $kValues[] = $range > 0 ? (($closes[$i] - $lowMin) / $range) * 100 : 50;
        }

        $kVal = round(end($kValues), 2);
        $recentK = array_slice($kValues, -$dPeriod);
        $dVal = round(array_sum($recentK) / count($recentK), 2);

        return ['k' => $kVal, 'd' => $dVal];
    }

    private function emptyResult(): array
    {
        return [
            'rsi'        => 50.0,
            'macd'       => ['macd' => 0, 'signal' => 0, 'histogram' => 0],
            'ema'        => [],
            'bb'         => ['upper' => 0, 'middle' => 0, 'lower' => 0],
            'atr'        => 0.0,
            'stochastic' => ['k' => 50.0, 'd' => 50.0],
        ];
    }
}
