import defaultTheme from 'tailwindcss/defaultTheme';
import colors from 'tailwindcss/colors';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/Support/Palette.php',
        './app/Enums/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: colors.indigo,
            },
            keyframes: {
                'pop-in': {
                    '0%': { opacity: 0, transform: 'scale(.9)' },
                    '100%': { opacity: 1, transform: 'scale(1)' },
                },
            },
            animation: {
                'pop-in': 'pop-in .25s ease-out',
            },
        },
    },

    plugins: [forms],
};
