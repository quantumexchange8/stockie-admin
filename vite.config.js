import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import vueJSX from '@vitejs/plugin-vue-jsx'
// import { VitePWA } from 'vite-plugin-pwa';

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
        vueJSX(),
        // VitePWA({
        //     registerType: 'autoUpdate',
        //     includeAssets: ['favicon.ico'],
        //     manifest: {
        //         name: 'Stockie Admin',
        //         short_name: 'Stockie Admin',
        //         description: 'Stockie Admin',
        //         theme_color: '#ffffff',
        //         background_color: '#ffffff',
        //         display: 'standalone',
        //         start_url: '/',
        //         icons: [
        //             {
        //                 src: '/images/icons/stockie-logo.png',
        //                 sizes: '192x192',
        //                 type: 'image/png',
        //             },
        //             {
        //                 src: '/images/icons/stockie-logo.png',
        //                 sizes: '512x512',
        //                 type: 'image/png',
        //                 purpose: 'any maskable',
        //             },
        //         ],
        //     },
        // }),
    ],
});
