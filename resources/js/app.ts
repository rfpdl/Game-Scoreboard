import './bootstrap';
import '../css/app.css';

import { createApp, h, DefineComponent } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createPinia } from 'pinia';
import Toast from '@/Components/atoms/Toast';

const appName = import.meta.env.VITE_APP_NAME || 'Game Scoreboard';

// Dark mode handling
function applyColorMode(colorMode: string) {
    const html = document.documentElement;

    if (colorMode === 'dark') {
        html.classList.add('dark');
    } else if (colorMode === 'system') {
        // Follow system preference
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }
    } else {
        html.classList.remove('dark');
    }
}

// Listen for system preference changes
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    const pageProps = (window as unknown as { __page?: { props?: { colorMode?: string } } }).__page?.props;
    if (pageProps?.colorMode === 'system') {
        if (e.matches) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<DefineComponent>('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();

        // Apply initial color mode
        const colorMode = (props.initialPage.props as { colorMode?: string }).colorMode || 'light';
        applyColorMode(colorMode);

        // Watch for color mode changes on navigation
        router.on('navigate', (event) => {
            const newColorMode = (event.detail.page.props as { colorMode?: string }).colorMode || 'light';
            applyColorMode(newColorMode);
        });

        createApp({
            render: () => [h(App, props), h(Toast)]
        })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
