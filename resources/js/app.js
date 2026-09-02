import { createApp, Fragment, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from 'ziggy-js';
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import '../css/app.css'
import { createI18n } from './i18n'
import FlashToasts from './components/FlashToasts.vue'

const toastOptions = {
    position: 'bottom-right',
    timeout: 3000,
    closeOnClick: true,
    pauseOnHover: true,
    draggable: true,
    icon: true,
}

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

        createApp({ render: () => h(Fragment, [h(App, props), h(FlashToasts)]) })
            .use(plugin)
            .use(createI18n(initialLocale))
            .use(ZiggyVue)
            .use(Toast, toastOptions)
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
