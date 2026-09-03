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
                    DEFAULT: '#08215B',
                    dark: '#05163F',
                    peach: '#FFE8CC',
                    accent: '#FB8000',
                },
                dusk: {
                    DEFAULT: '#08215B',
                    soft: '#E4EAF5',
                },
                gold: '#FB8000',
                ink: '#0F172A',
                mute: '#64748B',
                canvas: '#F5F7FB',
                parchment: '#EEF1F7',
                sage: '#E7F0E8',
                line: '#DCE3EF',
            },
            fontFamily: {
                sans: ['Outfit', ...defaultTheme.fontFamily.sans],
                brand: ['Fraunces', ...defaultTheme.fontFamily.serif],
            },
            boxShadow: {
                soft: '0 4px 16px rgba(8, 33, 91, 0.06)',
                lift: '0 12px 32px rgba(8, 33, 91, 0.10)',
            },
            borderRadius: {
                xl2: '1.125rem',
            },
        },
    },
    plugins: [],
};
