import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
  interface Window {
    Pusher: typeof Pusher;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    Echo: any;
  }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
let echo: Echo<'reverb'> | null = null;

export function getEcho(): Echo<'reverb'> {
  if (!echo && typeof window !== 'undefined') {
    window.Pusher = Pusher;

    echo = new Echo({
      broadcaster: 'reverb',
      key: process.env.NEXT_PUBLIC_REVERB_KEY ?? '',
      wsHost: process.env.NEXT_PUBLIC_REVERB_HOST ?? 'localhost',
      wsPort: Number(process.env.NEXT_PUBLIC_REVERB_PORT ?? 8080),
      wssPort: 443,
      forceTLS: process.env.NODE_ENV === 'production',
      enabledTransports: ['ws', 'wss'],
      disableStats: true,
    });
  }

  return echo!;
}