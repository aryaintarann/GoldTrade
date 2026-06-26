'use client';

import { useState } from 'react';

interface SignalData {
  direction: 'BUY' | 'SELL' | 'WAIT';
  entry_price?: number | null;
  stop_loss?: number | null;
  take_profits?: number[] | null;
  confidence: number;
  reasoning: string;
}

interface SignalCardProps {
  signal: SignalData;
  timestamp?: string;
}

const directionConfig = {
  BUY: { color: 'bg-emerald-500', textColor: 'text-emerald-400', border: 'border-emerald-500/30', label: '▲ BUY' },
  SELL: { color: 'bg-red-500', textColor: 'text-red-400', border: 'border-red-500/30', label: '▼ SELL' },
  WAIT: { color: 'bg-yellow-500', textColor: 'text-yellow-400', border: 'border-yellow-500/30', label: '◆ WAIT' },
};

export default function SignalCard({ signal, timestamp }: SignalCardProps) {
  const [expanded, setExpanded] = useState(false);
  const config = directionConfig[signal.direction];

  const confidenceColor =
    signal.confidence >= 80 ? 'bg-emerald-500' :
    signal.confidence >= 60 ? 'bg-blue-500' :
    signal.confidence >= 50 ? 'bg-yellow-500' : 'bg-red-500';

  return (
    <div className={`rounded-xl border ${config.border} bg-gray-900/80 p-4 my-2 backdrop-blur-sm`}>
      {/* Header */}
      <div className="flex items-center justify-between mb-3">
        <div className="flex items-center gap-3">
          <span className={`px-3 py-1 rounded-lg ${config.color} text-white font-bold text-lg tracking-wide`}>
            {config.label}
          </span>
          <span className="text-gray-400 text-sm">XAUUSD</span>
        </div>
        {timestamp && (
          <span className="text-gray-500 text-xs">{timestamp}</span>
        )}
      </div>

      {/* Confidence Bar */}
      <div className="mb-3">
        <div className="flex justify-between text-xs text-gray-400 mb-1">
          <span>Confidence</span>
          <span className={config.textColor}>{signal.confidence}%</span>
        </div>
        <div className="h-2 bg-gray-700 rounded-full overflow-hidden">
          <div
            className={`h-full ${confidenceColor} transition-all duration-500`}
            style={{ width: `${signal.confidence}%` }}
          />
        </div>
      </div>

      {/* Entry / SL / TP */}
      {signal.direction !== 'WAIT' && signal.entry_price && (
        <div className="grid grid-cols-3 gap-2 mb-3 text-center">
          <div className="bg-gray-800 rounded-lg p-2">
            <div className="text-xs text-gray-400">Entry</div>
            <div className="text-white font-mono font-semibold">{signal.entry_price?.toFixed(2)}</div>
          </div>
          <div className="bg-gray-800 rounded-lg p-2">
            <div className="text-xs text-red-400">Stop Loss</div>
            <div className="text-red-300 font-mono font-semibold">{signal.stop_loss?.toFixed(2) ?? '—'}</div>
          </div>
          <div className="bg-gray-800 rounded-lg p-2">
            <div className="text-xs text-emerald-400">TP 1</div>
            <div className="text-emerald-300 font-mono font-semibold">
              {signal.take_profits?.[0]?.toFixed(2) ?? '—'}
            </div>
          </div>
        </div>
      )}

      {/* Multiple TPs */}
      {signal.take_profits && signal.take_profits.length > 1 && (
        <div className="flex gap-2 mb-3">
          {signal.take_profits.slice(1).map((tp, i) => (
            <div key={i} className="bg-gray-800 rounded-lg p-2 flex-1 text-center">
              <div className="text-xs text-emerald-400">TP {i + 2}</div>
              <div className="text-emerald-300 font-mono text-sm">{tp.toFixed(2)}</div>
            </div>
          ))}
        </div>
      )}

      {/* Reasoning Accordion */}
      <button
        onClick={() => setExpanded(!expanded)}
        className="w-full text-left text-xs text-gray-400 hover:text-gray-200 transition-colors flex items-center gap-1"
      >
        <span>{expanded ? '▼' : '▶'}</span>
        <span>Lihat analisa teknikal</span>
      </button>

      {expanded && (
        <div className="mt-2 text-sm text-gray-300 leading-relaxed bg-gray-800/50 rounded-lg p-3">
          {signal.reasoning}
        </div>
      )}
    </div>
  );
}