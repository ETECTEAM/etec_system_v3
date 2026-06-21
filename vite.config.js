import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import path from 'path'

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
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
        host: '0.0.0.0',
        port: 5173,
        origin: 'http://127.0.0.1:5173',
        cors: {
            origin: [
                'http://127.0.0.1:8000',
                'http://localhost:8000',
                'http://127.0.0.1:8002',
                'http://localhost:8002',
            ],
            methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            allowedHeaders: ['Content-Type', 'Authorization', 'X-Requested-With'],
        },
        // កំណត់យកតែ 127.0.0.1 សម្រាប់រត់លើម៉ាស៊ីនផ្ទាល់ខ្លួនធម្មតា
        hmr: {
            host: '127.0.0.1',
        },
        watch: {
            usePolling: true,
        },
    },
})