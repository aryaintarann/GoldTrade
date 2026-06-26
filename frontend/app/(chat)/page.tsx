import dynamic from 'next/dynamic';
import PriceTicker from '@/components/PriceTicker';
import IndicatorSidebar from '@/components/IndicatorSidebar';

const ChatPanel = dynamic(() => import('@/components/ChatPanel'), { ssr: false });
const TradingViewWidget = dynamic(() => import('@/components/TradingViewWidget'), { ssr: false });

export default function ChatPage() {
  return (
    <div className="flex flex-col h-screen">
      {/* Top Bar */}
      <header className="flex items-center justify-between px-4 py-3 border-b border-gray-800/60 bg-gray-950/80 backdrop-blur-sm z-10">
        <div className="flex items-center gap-3">
          <span className="text-lg font-bold text-white">⚡ GoldAI Scalper</span>
          <span className="text-xs text-gray-500 bg-gray-800 px-2 py-0.5 rounded">v3.0</span>
        </div>
        <PriceTicker />
        <nav className="flex gap-2">
          <a href="/dashboard" className="text-sm text-gray-400 hover:text-white transition-colors px-3 py-1 rounded-lg hover:bg-gray-800">
            Dashboard
          </a>
        </nav>
      </header>

      {/* 3-column layout */}
      <div className="flex flex-1 overflow-hidden">
        {/* Left: Indicator Sidebar */}
        <aside className="hidden lg:block w-72 shrink-0 overflow-y-auto">
          <IndicatorSidebar />
        </aside>

        {/* Center: Chat */}
        <main className="flex-1 overflow-hidden border-x border-gray-800/50">
          <ChatPanel />
        </main>

        {/* Right: TradingView Chart */}
        <aside className="hidden xl:block w-80 shrink-0">
          <TradingViewWidget />
        </aside>
      </div>
    </div>
  );
}