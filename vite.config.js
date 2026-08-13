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
    // 🚀 THE FIX: Split the giant 2.5MB file into smaller, safer chunks
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        return 'vendor'; // Groups all heavy libraries into one separate file
                    }
                }
            }
        }
    }
});