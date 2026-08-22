import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                display: ['"Baloo 2"', ...defaultTheme.fontFamily.sans],
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"Fira Code"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                canvas: 'rgb(var(--color-canvas) / <alpha-value>)',
                surface: 'rgb(var(--color-surface) / <alpha-value>)',
                border: 'rgb(var(--color-border) / <alpha-value>)',
                ink: {
                    muted: 'rgb(var(--color-text-muted) / <alpha-value>)',
                    secondary: 'rgb(var(--color-text-secondary) / <alpha-value>)',
                    primary: 'rgb(var(--color-text-primary) / <alpha-value>)',
                },
                brand: {
                    DEFAULT: 'rgb(var(--color-brand) / <alpha-value>)',
                    dark: 'rgb(var(--color-brand-dark) / <alpha-value>)',
                },
                accent: {
                    DEFAULT: 'rgb(var(--color-accent) / <alpha-value>)',
                    dark: 'rgb(var(--color-accent-dark) / <alpha-value>)',
                },
                gold: {
                    DEFAULT: 'rgb(var(--color-gold) / <alpha-value>)',
                    dark: 'rgb(var(--color-gold-dark) / <alpha-value>)',
                },
                danger: {
                    DEFAULT: 'rgb(var(--color-danger) / <alpha-value>)',
                },
            },
            borderRadius: {
                DEFAULT: '0.75rem',
            },
        },
    },

    plugins: [forms, typography],
};
