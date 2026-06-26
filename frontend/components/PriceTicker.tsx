'use client';

import { useEffect, useState } from 'react';

interface PriceData {
  price: number;
  change: number;
  changePercent: number;
}

export default function PriceTicker() {
  const [data, setData] = useState<PriceData | null>(null);
  const [connected, setConnected] = useState(false);

  useEffect(() => {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    let channel: any = null;

    async function initEcho() {
      const { getEcho } = await import('@/lib/echo');
      const echo = getEcho();

      channel = echo.channel('xauusd-price');
      channel.listen('.price.updated', (e: PriceData) => {
        setData(e);
        setConnected(true);
      });

      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      const connector = echo.connector as any;
      connector?.socket?.on?.('connect', () => setConnected(true));
      connector?.socket?.on?.('disconnect', () => setConnected(false));
    }

    initEcho().catch(console.error);

    return () => {
      channel?.stopListening?.('.price.updated');
    };
  }, []);

  const isPositive = (data?.change ?? 0) >= 0;

  return (
    <div className="flex items-center gap-3 bg-gray-900/60 border border-gray-700/50 rounded-xl px-4 py-2">
      <div className="flex items-center gap-1.5">
        <div className={`w-2 h-2 rounded-full ${connected ? 'bg-emerald-400 animate-pulse' : 'bg-gray-500'}`} />
        <span className="text-xs text-gray-400">XAU/USD</span>
      </div>

      {data ? (
        <>
          <span className="text-xl font-mono font-bold text-white">
            {data.price.toFixed(2)}
          </span>
          <span className={`text-sm font-mono ${isPositive ? 'text-emerald-400' : 'text-red-400'}`}>
            {isPositive ? '+' : ''}{data.change.toFixed(2)} ({isPositive ? '+' : ''}{data.changePercent.toFixed(3)}%)
          </span>
        </>
      ) : (
        <span className="text-gray-500 text-sm animate-pulse">Connecting...</span>
      )}
    </div>
  );
}