'use client';

import { useEffect, useState } from 'react';

interface IndicatorData {
  rsi: number;
  macd: { macd: number; signal: number; histogram: number };
  ema: { ema9?: number; ema21?: number; ema50?: number; ema200?: number };
  atr: number;
  stochastic: { k: number; d: number };
}

const timeframes = ['M1', 'M5', 'M15', 'H1', 'H4'];

function RSIMeter({ value }: { value: number }) {
  const color = value > 70 ? 'text-red-400' : value < 30 ? 'text-emerald-400' : 'text-blue-400';
  const label = value > 70 ? 'OB' : value < 30 ? 'OS' : 'NTR';
  return (
    <div className="flex items-center justify-between">
      <span className="text-xs text-gray-400">RSI(14)</span>
      <div className="flex items-center gap-1.5">
        <span className={`text-xs font-mono ${color}`}>{value.toFixed(1)}</span>
        <span className={`text-xs px-1 rounded ${color}`}>{label}</span>
      </div>
    </div>
  );
}

export default function IndicatorSidebar() {
  const [activeTimeframe, setActiveTimeframe] = useState('M15');
  const [indicators, setIndicators] = useState<IndicatorData | null>(null);

  useEffect(() => {
    // Mock indicator data - in production this would fetch from /api/indicators
    setIndicators({
      rsi: 58.4,
      macd: { macd: 0.42, signal: 0.31, histogram: 0.11 },
      ema: { ema9: 2381.5, ema21: 2379.2, ema50: 2375.8, ema200: 2360.1 },
      atr: 4.82,
      stochastic: { k: 62.3, d: 58.1 },
    });
  }, [activeTimeframe]);

  return (
    <div className="flex flex-col h-full bg-gray-950 border-r border-gray-800/50 p-4 overflow-y-auto">
      <h3 className="text-sm font-semibold text-gray-300 mb-4 flex items-center gap-2">
        <span className="w-2 h-2 bg-blue-400 rounded-full" />
        Indikator Teknikal
      </h3>

      {/* Timeframe selector */}
      <div className="flex gap-1 mb-4 flex-wrap">
        {timeframes.map((tf) => (
          <button
            key={tf}
            onClick={() => setActiveTimeframe(tf)}
            className={`text-xs px-2 py-1 rounded-md transition-colors ${
              activeTimeframe === tf
                ? 'bg-blue-600 text-white'
                : 'bg-gray-800 text-gray-400 hover:bg-gray-700'
            }`}
          >
            {tf}
          </button>
        ))}
      </div>

      {indicators && (
        <div className="space-y-3">
          <RSIMeter value={indicators.rsi} />

          <div className="space-y-1">
            <span className="text-xs text-gray-400">MACD</span>
            <div className="bg-gray-800/60 rounded-lg p-2 space-y-1">
              <div className="flex justify-between text-xs">
                <span className="text-gray-500">Line</span>
                <span className={indicators.macd.macd > 0 ? 'text-emerald-400' : 'text-red-400'}>
                  {indicators.macd.macd.toFixed(3)}
                </span>
              </div>
              <div className="flex justify-between text-xs">
                <span className="text-gray-500">Signal</span>
                <span className="text-blue-400">{indicators.macd.signal.toFixed(3)}</span>
              </div>
              <div className="flex justify-between text-xs">
                <span className="text-gray-500">Hist</span>
                <span className={indicators.macd.histogram > 0 ? 'text-emerald-400' : 'text-red-400'}>
                  {indicators.macd.histogram.toFixed(3)}
                </span>
              </div>
            </div>
          </div>

          <div className="space-y-1">
            <span className="text-xs text-gray-400">EMA</span>
            <div className="bg-gray-800/60 rounded-lg p-2 space-y-1">
              {Object.entries(indicators.ema).map(([key, val]) => (
                <div key={key} className="flex justify-between text-xs">
                  <span className="text-gray-500">{key.toUpperCase()}</span>
                  <span className="text-gray-300 font-mono">{val?.toFixed(2)}</span>
                </div>
              ))}
            </div>
          </div>

          <div className="flex items-center justify-between">
            <span className="text-xs text-gray-400">ATR(14)</span>
            <span className="text-xs font-mono text-orange-400">{indicators.atr.toFixed(2)}</span>
          </div>

          <div className="space-y-1">
            <span className="text-xs text-gray-400">Stochastic</span>
            <div className="bg-gray-800/60 rounded-lg p-2">
              <div className="flex justify-between text-xs">
                <span className="text-gray-500">%K</span>
                <span className="text-purple-400">{indicators.stochastic.k.toFixed(1)}</span>
              </div>
              <div className="flex justify-between text-xs mt-1">
                <span className="text-gray-500">%D</span>
                <span className="text-purple-300">{indicators.stochastic.d.toFixed(1)}</span>
              </div>
            </div>
          </div>

          {/* Bias summary */}
          <div className="mt-4 bg-gray-800/40 rounded-lg p-3 border border-gray-700/30">
            <div className="text-xs text-gray-400 mb-2">Signal Strength</div>
            <div className="flex gap-1">
              {[1,2,3,4,5].map(i => (
                <div
                  key={i}
                  className={`flex-1 h-2 rounded-full ${
                    i <= 3 ? 'bg-blue-500' : 'bg-gray-700'
                  }`}
                />
              ))}
            </div>
            <div className="text-xs text-blue-400 mt-1 text-center">Moderate</div>
          </div>
        </div>
      )}
    </div>
  );
}