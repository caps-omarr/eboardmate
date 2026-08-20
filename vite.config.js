import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
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
        VitePWA({
            outDir: 'public/build', 
            buildBase: '/build/',
            registerType: 'autoUpdate',
            injectRegister: 'script',
            manifest: {
                name: 'E-BoardMate Owner Portal',
                short_name: 'E-BoardMate',
                description: 'Manage your boarding house and reservations on the go.',
                theme_color: '#10b981', 
                background_color: '#ffffff',
                display: 'standalone', 
                scope: '/',
                start_url: '/owner/dashboard', 
                icons: [
                    {
                        src: '/favicon.png', 
                        sizes: '192x192',
                        type: 'image/png'
                    },
                    {
                        src: '/favicon.png', 
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any maskable'
                    }
                ]
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
                navigateFallback: null, 
                maximumFileSizeToCacheInBytes: 4000000,
            }
        })
    ],
    // 🚀 CODE SPLITTING FIX: Prevents 2MB+ monolithic vendor files that cause ERR_HTTP2_PROTOCOL_ERROR on Cloudflare Tunnels
    build: {
        chunkSizeWarningLimit: 1500,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('mapbox-gl')) {
                            return 'mapbox';
                        }
                        if (id.includes('bootstrap') || id.includes('@popperjs')) {
                            return 'bootstrap';
                        }
                        if (id.includes('vue') || id.includes('@inertiajs')) {
                            return 'vue-core';
                        }
                        return 'vendor';
                    }
                }
            }
        }
    }
});