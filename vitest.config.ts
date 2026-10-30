import react from '@vitejs/plugin-react';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vitest/config';

// Config separada do vite.config.js: o laravel-vite-plugin e o Tailwind não
// fazem nada útil em teste e o plugin do Laravel exige o manifesto do build.
export default defineConfig({
    plugins: [react()],
    esbuild: { jsx: 'automatic' },
    resolve: {
        alias: { '@': fileURLToPath(new URL('./resources/js', import.meta.url)) },
    },
    test: {
        environment: 'jsdom',
        // Sem globals, o Testing Library não limpa o DOM sozinho entre os testes.
        setupFiles: ['./vitest.setup.tsx'],
        include: ['resources/js/**/*.test.{ts,tsx}'],
        coverage: {
            provider: 'v8',
            // lcov é o formato que o SonarQube importa (sonar.javascript.lcov.reportPaths).
            reporter: ['text-summary', 'lcov'],
            include: ['resources/js/**/*.{ts,tsx}'],
            // components/ui é saída do shadcn (não editar nem testar à mão).
            exclude: ['resources/js/components/ui/**', 'resources/js/**/*.test.{ts,tsx}', 'resources/js/test/**', 'resources/js/types/**'],
        },
    },
});
