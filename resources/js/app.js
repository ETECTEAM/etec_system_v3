// Side-effect import FIRST: the PWA install composable attaches its
// beforeinstallprompt/appinstalled listeners at import time, so they must be
// live before any code-split page component (and before createInertiaApp) runs.
import './composables/usePwaInstall'
import { createApp, Fragment, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from 'ziggy-js';
import '../css/app.css'
import { createI18n } from './i18n'
import FlashToasts from './components/FlashToasts.vue'
import ToastHost from './components/ToastHost.vue'

createInertiaApp({
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob('./pages/**/*.vue')),
    progress: {
        delay: 0,
        color: '#0b72a5',
        includeCSS: true,
        showSpinner: false,
    },
    setup({ el, App, props, plugin }) {
        const initialLocale = props.initialPage?.props?.locale?.current ?? 'en'

        createApp({ render: () => h(Fragment, [h(App, props), h(FlashToasts), h(ToastHost)]) })
            .use(plugin)
            .use(createI18n(initialLocale))
            .use(ZiggyVue)
            .mount(el)
    },
})

// Register the PWA service worker once the app is interactive. Only runs in
// the browser (this entry is never SSR'd), guarded for older browsers.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {})
    })
}
