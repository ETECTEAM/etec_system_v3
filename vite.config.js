import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import path from 'path'

export default defineConfig({
    plugins: [
        vue(),

        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),

        tailwindcss(),
    ],

    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },

    server: {
        // Use 0.0.0.0 because you are inside Docker/container
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,

        hmr: {
            host: '127.0.0.1',
            port: 5173,
        },

        watch: {
            usePolling: true,
        },
    },
})
