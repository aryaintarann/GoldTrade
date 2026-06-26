'use client';

import { useEffect, useRef, useState } from 'react';
import { useChat } from '@ai-sdk/react';
import { DefaultChatTransport } from 'ai';
import SignalCard from './SignalCard';
import { fetchCsrfCookie } from '@/lib/api';

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000';

const QUICK_ACTIONS = [
  'Berikan analisa XAUUSD sekarang',
  'Analisa multi-timeframe H1 dan H4',
  'Cek apakah ada setup scalping M5',
  'Apakah ada berita high-impact hari ini?',
];

interface StructuredSignal {
  direction: 'BUY' | 'SELL' | 'WAIT';
  entry_price?: number;
  stop_loss?: number;
  take_profits?: number[];
  confidence: number;
  reasoning: string;
}

function extractSignal(text: string): StructuredSignal | null {
  try {
    const match = text.match(/\{[^{}]*"direction"[^{}]*\}/);
    if (match) return JSON.parse(match[0]);
  } catch {
    // plain text
  }
  return null;
}

function getMessageText(message: { parts?: Array<{ type: string; text?: string }> }): string {
  if (!message.parts) return '';
  return message.parts
    .filter((p) => p.type === 'text')
    .map((p) => p.text ?? '')
    .join('');
}

export default function ChatPanel() {
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const [input, setInput] = useState('');
  const [csrfReady, setCsrfReady] = useState(false);

  useEffect(() => {
    fetchCsrfCookie()
      .then(() => setCsrfReady(true))
      .catch(console.error);
  }, []);

  const { messages, sendMessage, status } = useChat({
    transport: new DefaultChatTransport({
      api: `${API_URL}/api/chat`,
      credentials: 'include',
    }),
  });

  const isLoading = status === 'streaming' || status === 'submitted';

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  const handleSend = (text?: string) => {
    const msg = (text ?? input).trim();
    if (!msg || !csrfReady || isLoading) return;
    sendMessage({ text: msg });
    setInput('');
  };

  return (
    <div className="flex flex-col h-full">
      <div className="flex-1 overflow-y-auto px-4 py-4 space-y-4">
        {messages.length === 0 && (
          <div className="text-center py-12">
            <div className="text-4xl mb-4">âš¡</div>
            <h2 className="text-xl font-semibold text-white mb-2">GoldAI Scalper</h2>
            <p className="text-gray-400 text-sm">Analisa XAUUSD dengan AI multi-timeframe</p>
            <div className="grid grid-cols-2 gap-2 mt-6 max-w-sm mx-auto">
              {QUICK_ACTIONS.map((action) => (
                <button
                  key={action}
                  onClick={() => handleSend(action)}
                  className="text-left text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg p-3 transition-colors border border-gray-700/50"
                >
                  {action}
                </button>
              ))}
            </div>
          </div>
        )}

        {messages.map((message) => {
          const isUser = message.role === 'user';
          const text = getMessageText(message as Parameters<typeof getMessageText>[0]);
          const signal = !isUser ? extractSignal(text) : null;

          return (
            <div key={message.id} className={`flex ${isUser ? 'justify-end' : 'justify-start'}`}>
              {isUser ? (
                <div className="bg-blue-600 text-white rounded-2xl rounded-tr-sm px-4 py-2 max-w-xs text-sm">
                  {text}
                </div>
              ) : (
                <div className="max-w-lg w-full">
                  {signal ? (
                    <SignalCard
                      signal={signal}
                      timestamp={new Date().toLocaleTimeString('id-ID')}
                    />
                  ) : (
                    <div className="bg-gray-800 rounded-2xl rounded-tl-sm px-4 py-3 text-gray-200 text-sm leading-relaxed">
                      {text}
                    </div>
                  )}
                </div>
              )}
            </div>
          );
        })}

        {isLoading && (
          <div className="flex justify-start">
            <div className="bg-gray-800 rounded-2xl rounded-tl-sm px-4 py-3">
              <div className="flex gap-1.5">
                <span className="w-2 h-2 bg-blue-400 rounded-full animate-bounce" style={{ animationDelay: '0ms' }} />
                <span className="w-2 h-2 bg-blue-400 rounded-full animate-bounce" style={{ animationDelay: '150ms' }} />
                <span className="w-2 h-2 bg-blue-400 rounded-full animate-bounce" style={{ animationDelay: '300ms' }} />
              </div>
            </div>
          </div>
        )}

        <div ref={messagesEndRef} />
      </div>

      <div className="border-t border-gray-800 p-4">
        <div className="flex gap-2">
          <input
            type="text"
            value={input}
            onChange={(e) => setInput(e.target.value)}
            onKeyDown={(e) => e.key === 'Enter' && !e.shiftKey && handleSend()}
            placeholder={csrfReady ? 'Tanya analisa XAUUSD...' : 'Connecting...'}
            disabled={!csrfReady || isLoading}
            className="flex-1 bg-gray-800 border border-gray-700 text-white rounded-xl px-4 py-3 text-sm placeholder:text-gray-500 focus:outline-none focus:border-blue-500 disabled:opacity-50"
          />
          <button
            onClick={() => handleSend()}
            disabled={!input.trim() || !csrfReady || isLoading}
            className="bg-blue-600 hover:bg-blue-500 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-xl px-4 py-3 transition-colors"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  );
}