import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    build: {
        // pdfmake incluye las fuentes embebidas y solo se carga en las tablas con exportación.
        chunkSizeWarningLimit: 2500,
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
