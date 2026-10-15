import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import tailwindcss from '@tailwindcss/vite'
import { VitePWA } from 'vite-plugin-pwa'

// https://vite.dev/config/
export default defineConfig({
    plugins: [
        tailwindcss(),
        vue(),
        vueDevTools(),
        VitePWA({
            registerType: 'autoUpdate',
            // Le manifeste existe déjà dans public/favicon et est référencé par index.html.
            manifest: false,
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
                globIgnores: ['**/og-image.png'],
                navigateFallback: '/index.html',
                navigateFallbackDenylist: [/^\/api\//, /^\/sanctum\//],
                runtimeCaching: [
                    {
                        urlPattern: ({ url, sameOrigin }) =>
                            sameOrigin &&
                            url.pathname.startsWith('/data/') &&
                            url.pathname.endsWith('.json'),
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'data-json',
                            networkTimeoutSeconds: 3,
                            expiration: { maxEntries: 10, maxAgeSeconds: 60 * 60 * 24 * 30 },
                        },
                    },
                ],
            },
        }),
    ],
    server: {
        watch: {
            ignored: ['**/public/**/*.{png,jpg,jpeg,webp,ico}'],
        },
    },
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./src', import.meta.url)),
        },
    },
})
