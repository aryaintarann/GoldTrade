'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000';

interface Performance {
  total: number; win_rate: number; tp_hits: number; sl_hits: number;
  by_direction: Record<string, { total: number; win_rate: number }>;
  by_confidence: Array<{ range: string; total: number; win_rate: number }>;
}

interface Signal {
  id: number; direction: string; confidence: number;
  entry_price: string; outcome: string; created_at: string;
}

export default function DashboardPage() {
  const [perf, setPerf] = useState<Performance | null>(null);
  const [signals, setSignals] = useState<Signal[]>([]);

  useEffect(() => {
    const headers = { Accept: 'application/json' };
    fetch(`${API_URL}/api/signals/performance`, { credentials: 'include', headers })
      .then(r => r.json()).then(setPerf).catch(console.error);
    fetch(`${API_URL}/api/signals`, { credentials: 'include', headers })
      .then(r => r.json()).then(d => setSignals(d.data ?? [])).catch(console.error);
  }, []);

  const outcomeColor = (o: string) =>
    o === 'tp_hit' ? 'text-emerald-400' : o === 'sl_hit' ? 'text-red-400' :
    o === 'expired' ? 'text-gray-500' : 'text-yellow-400';

  return (
    <div className="min-h-screen bg-gray-950 p-6">
      <div className="max-w-5xl mx-auto">
        <div className="flex items-center justify-between mb-6">
          <h1 className="text-xl font-bold text-white">📊 Performance Dashboard</h1>
          <Link href="/" className="text-sm text-gray-400 hover:text-white bg-gray-800 px-3 py-1.5 rounded-lg">
            ← Chat
          </Link>
        </div>

        {perf && (
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {[
              { label: 'Total Signals', value: perf.total },
              { label: 'Win Rate', value: `${perf.win_rate}%` },
              { label: 'TP Hit', value: perf.tp_hits },
              { label: 'SL Hit', value: perf.sl_hits },
            ].map(({ label, value }) => (
              <div key={label} className="bg-gray-900 rounded-xl border border-gray-800 p-4 text-center">
                <div className="text-2xl font-bold text-white">{value}</div>
                <div className="text-xs text-gray-400 mt-1">{label}</div>
              </div>
            ))}
          </div>
        )}

        <div className="bg-gray-900 rounded-xl border border-gray-800 overflow-hidden">
          <div className="px-4 py-3 border-b border-gray-800">
            <h2 className="text-sm font-semibold text-gray-300">Signal Journal</h2>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-gray-800">
                  <th className="text-left px-4 py-2 text-gray-400 text-xs">Waktu</th>
                  <th className="text-left px-4 py-2 text-gray-400 text-xs">Arah</th>
                  <th className="text-left px-4 py-2 text-gray-400 text-xs">Entry</th>
                  <th className="text-left px-4 py-2 text-gray-400 text-xs">Confidence</th>
                  <th className="text-left px-4 py-2 text-gray-400 text-xs">Outcome</th>
                </tr>
              </thead>
              <tbody>
                {signals.length === 0 ? (
                  <tr><td colSpan={5} className="text-center text-gray-500 py-8">Belum ada signal</td></tr>
                ) : signals.map(s => (
                  <tr key={s.id} className="border-b border-gray-800/50 hover:bg-gray-800/30">
                    <td className="px-4 py-2 text-gray-400 text-xs">{new Date(s.created_at).toLocaleString('id-ID')}</td>
                    <td className="px-4 py-2">
                      <span className={`font-semibold ${s.direction === 'BUY' ? 'text-emerald-400' : s.direction === 'SELL' ? 'text-red-400' : 'text-yellow-400'}`}>
                        {s.direction}
                      </span>
                    </td>
                    <td className="px-4 py-2 font-mono text-gray-300">{s.entry_price ?? '—'}</td>
                    <td className="px-4 py-2 text-blue-400">{s.confidence}%</td>
                    <td className={`px-4 py-2 ${outcomeColor(s.outcome)}`}>{s.outcome}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}