import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    // Bind dev server & HMR ke localhost (bukan [::1]) agar handshake WebSocket
    // HMR tidak gagal di Windows (mismatch localhost vs ::1).
    server: {
        host: 'localhost',
        hmr: {
            host: 'localhost',
        },
    },
});
