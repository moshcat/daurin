import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Echo: Echo<'reverb'>;
        Pusher: typeof Pusher;
    }
}

// Reverb berbicara protokol Pusher, jadi pusher-js dipakai sebagai client —
// namun koneksi mengarah ke server Reverb milik sendiri (VITE_REVERB_HOST),
// bukan ke layanan cloud Pusher.
if (typeof window !== 'undefined') {
    window.Pusher = Pusher;

    window.Echo = new Echo<'reverb'>({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
