import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js',
                                __dirname + '/Modules/Admin/resources/assets/js/bootstrap-icons.js',
                __dirname + '/Modules/Admin/resources/assets/js/nucleo-icons.js',
                __dirname + '/Modules/Admin/resources/assets/js/nucleo-svg.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
