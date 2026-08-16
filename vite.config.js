import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import {
    defineConfig
} from 'vite';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.jsx',
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    esbuild: {
        jsx: 'automatic',
    },
    server: {
        // permite abrir o dev server pelo IP da rede local (celular no
        // mesmo wi-fi), não só localhost.
        host: '0.0.0.0',
        // script type="module" exige CORS mesmo em rede local -- sem isso os
        // módulos carregam via curl (200) mas o browser bloqueia a execução
        cors: true,
    },
});