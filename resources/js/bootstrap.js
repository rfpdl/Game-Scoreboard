import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

// Also set CSRF token from meta tag for backup
const csrfToken = document.head.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
}

// Laravel Echo configuration for Reverb (only if configured)
// Real-time features are optional - the app works without them
const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const reverbHost = import.meta.env.VITE_REVERB_HOST;

if (reverbKey && reverbHost) {
    // Only load Pusher and Echo when WebSockets are configured
    import('pusher-js').then((PusherModule) => {
        import('laravel-echo').then((EchoModule) => {
            const Pusher = PusherModule.default;
            const Echo = EchoModule.default;

            window.Pusher = Pusher;

            try {
                window.Echo = new Echo({
                    broadcaster: 'reverb',
                    key: reverbKey,
                    wsHost: reverbHost,
                    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
                    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
                    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
                    enabledTransports: ['ws', 'wss'],
                });

                window.Echo.connector.pusher.connection.bind('connected', () => {
                    console.log('Real-time updates enabled (connected to Reverb)');
                });

                window.Echo.connector.pusher.connection.bind('error', (error) => {
                    console.warn('Real-time connection error:', error);
                });
            } catch (error) {
                console.warn('Real-time updates unavailable:', error.message);
            }
        });
    });
} else {
    console.log('Real-time updates disabled (Reverb not configured)');
}
