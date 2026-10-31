import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // Bootstrap 5 is compiled from SCSS; both entries are loaded by @vite().
            input: ['resources/sass/app.scss', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                // Let Sass find `bootstrap` inside node_modules.
                loadPaths: ['node_modules'],
                quietDeps: true,
                // Bootstrap 5.3 still uses @import internally; hide those notices.
                silenceDeprecations: ['import', 'global-builtin', 'color-functions'],
            },
        },
    },
});
