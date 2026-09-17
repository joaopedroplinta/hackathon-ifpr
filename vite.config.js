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
        //
        // ATENÇÃO: nem '0.0.0.0' nem `true` fazem o laravel-vite-plugin
        // gravar o IP real da rede em public/hot -- ele grava o host cru
        // ('0.0.0.0' ou '[::]'), que o navegador do celular não consegue
        // abrir. Testado em 2026-09-09 com vite 6.1.1 + plugin 1.2.0. Pra
        // testar do celular, rode com o IP da máquina explícito em vez de
        // `npm run dev`:
        //   npx vite --host <IP-da-máquina-na-rede>
        host: '0.0.0.0',
        // script type="module" exige CORS mesmo em rede local -- sem isso os
        // módulos carregam via curl (200) mas o browser bloqueia a execução
        cors: true,
    },
});