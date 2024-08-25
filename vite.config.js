// noinspection JSUnusedGlobalSymbols

import { defineConfig } from 'vite';
import laravel, { refreshPaths } from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import findMingles from './vendor/ijpatricio/mingle/resources/js/autoImport.js';

const mingles = findMingles('resources/js');

export default defineConfig({
    resolve: {
        alias: {
            '@mingle': path.resolve(
                __dirname,
                '/vendor/ijpatricio/mingle/resources/js',
            ),
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    plugins: [
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/web.css',
                'resources/js/app.js',
                'resources/js/web.js',
                'resources/css/filament/admin/theme.css',
                ...mingles,
            ],
            refresh: [
                ...refreshPaths,
                'resources/js/**',
                'app/Filament/**',
                'app/Forms/Components/**',
                'app/Livewire/**',
                'app/Infolists/Components/**',
                'app/Providers/Filament/**',
                'app/Tables/Columns/**',
            ],
        }),
    ],
});
