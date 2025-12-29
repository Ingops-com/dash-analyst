import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher?: typeof Pusher;
    }
}

let echoInstance: Echo | null = null;

export const getEcho = (): Echo | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    if (echoInstance) {
        return echoInstance;
    }

    const key = import.meta.env.VITE_PUSHER_APP_KEY;
    if (!key) {
        return null;
    }

    window.Pusher = Pusher;

    const host = import.meta.env.VITE_PUSHER_HOST || window.location.hostname;
    const port = Number(import.meta.env.VITE_PUSHER_PORT ?? 6001);
    const scheme = (import.meta.env.VITE_PUSHER_SCHEME ?? 'https').toLowerCase();
    const forceTLS = scheme === 'https';

    echoInstance = new Echo({
        broadcaster: 'pusher',
        key,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
        wsHost: host,
        wssHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS,
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            },
        },
    });

    return echoInstance;
};
