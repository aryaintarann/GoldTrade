<?php

namespace App\Services;

class ConfidenceScoreService
{
    public function calculate(array $indicatorsPerTimeframe, bool $hasHighImpactNews = false): int
    {
        $score = 0;

        // EMA trend alignment multi-timeframe (25%)
        $score += $this->scoreEmaTrend($indicatorsPerTimeframe) * 25;

        // RSI + Stochastic alignment (20%)
        $score += $this->scoreMomentum($indicatorsPerTimeframe) * 20;

        // MACD crossover support (20%)
        $score += $this->scoreMacd($indicatorsPerTimeframe) * 20;

        // S/R & Bollinger Band position (20%)
        $score += $this->scoreBollingerPosition($indicatorsPerTimeframe) * 20;

        // No high-impact news in 2h (15%)
        if (!$hasHighImpactNews) {
            $score += 15;
        }

        return min(100, max(0, (int) round($score)));
    }

    private function scoreEmaTrend(array $data): float
    {
        $bullishCount = 0;
        $total = 0;

        foreach ($data as $tf => $indicators) {
            $ema = $indicators['ema'] ?? [];
            if (isset($ema['ema9'], $ema['ema21'], $ema['ema50'])) {
                $total++;
                if ($ema['ema9'] > $ema['ema21'] && $ema['ema21'] > $ema['ema50']) {
                    $bullishCount++;
                }
            }
        }

        return $total > 0 ? $bullishCount / $total : 0.5;
    }

    private function scoreMomentum(array $data): float
    {
        $alignedCount = 0;
        $total = 0;

        foreach ($data as $indicators) {
            $rsi   = $indicators['rsi'] ?? 50;
            $stoch = $indicators['stochastic'] ?? ['k' => 50, 'd' => 50];

            $rsiBullish   = $rsi > 50 && $rsi < 70;
            $stochBullish = ($stoch['k'] ?? 50) > ($stoch['d'] ?? 50) && ($stoch['k'] ?? 50) < 80;

            if ($rsiBullish === $stochBullish) {
                $alignedCount++;
            }
            $total++;
        }

        return $total > 0 ? $alignedCount / $total : 0.5;
    }

    private function scoreMacd(array $data): float
    {
        $positiveCount = 0;
        $total = 0;

        foreach ($data as $indicators) {
            $macd = $indicators['macd'] ?? ['histogram' => 0];
            $total++;
            if (($macd['histogram'] ?? 0) > 0) {
                $positiveCount++;
            }
        }

        return $total > 0 ? $positiveCount / $total : 0.5;
    }

    private function scoreBollingerPosition(array $data): float
    {
        $goodCount = 0;
        $total = 0;

        foreach ($data as $indicators) {
            $bb    = $indicators['bb'] ?? null;
            $price = $indicators['last_price'] ?? null;

            if ($bb && $price) {
                $middle = $bb['middle'] ?? $price;
                $upper  = $bb['upper'] ?? $price;
                $lower  = $bb['lower'] ?? $price;
                $total++;

                // Price near lower band (buy zone) or near upper band (sell zone)
                $range = $upper - $lower;
                if ($range > 0) {
                    $position = ($price - $lower) / $range;
                    // Score high if near middle (not extended)
                    if ($position >= 0.3 && $position <= 0.7) {
                        $goodCount++;
                    }
                }
            }
        }

        return $total > 0 ? $goodCount / $total : 0.5;
    }
}
