import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Monochrome "black" primary scale.
                brand: {
                    50: '#f6f6f6',
                    100: '#ededed',
                    200: '#e0e0e0',
                    300: '#c9c9c9',
                    400: '#9d9d9d',
                    500: '#525252',
                    600: '#1f1f1f',
                    700: '#141414',
                    800: '#0a0a0a',
                    900: '#000000',
                },
            },
            boxShadow: {
                soft: '0 1px 3px 0 rgba(15, 23, 42, 0.06), 0 1px 2px -1px rgba(15, 23, 42, 0.06)',
                card: '0 4px 24px -8px rgba(15, 23, 42, 0.10)',
            },
        },
    },

    plugins: [forms],
};
