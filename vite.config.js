import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';
import { fileURLToPath } from 'url';

// const __filename = fileURLToPath(import.meta.url);
// const __dirname = path.dirname(__filename);

export default defineConfig({
    resolve: {
        alias: {
            // '@': path.resolve(__dirname, './resources/js'),
            ziggy: path.resolve('vendor/tightenco/ziggy/dist'),
            'ziggy-js': path.resolve('vendor/tightenco/ziggy/dist/index.esm.js'),
        }
    },
    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
        }),
        react(),
    ],
});
