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
    progress: false,
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
