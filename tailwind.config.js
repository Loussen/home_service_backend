import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './public/js/web-app.js',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    DEFAULT: '#C24E2D',
                    dark: '#9A3B22',
                    peach: '#F3E4DE',
                },
                dusk: {
                    DEFAULT: '#3D4F7C',
                    soft: '#E8EEF6',
                },
                gold: '#D4A84B',
                ink: '#1C1917',
                mute: '#7A746C',
                canvas: '#F7F4EF',
                parchment: '#F1EBE3',
                sage: '#E7F0E8',
                line: '#E4DED6',
            },
            fontFamily: {
                sans: ['"DM Sans"', ...defaultTheme.fontFamily.sans],
                brand: ['Fraunces', ...defaultTheme.fontFamily.serif],
            },
            boxShadow: {
                soft: '0 4px 16px rgba(28, 25, 23, 0.05)',
                lift: '0 12px 32px rgba(28, 25, 23, 0.08)',
            },
            borderRadius: {
                xl2: '1.125rem',
            },
        },
    },
    plugins: [],
};
