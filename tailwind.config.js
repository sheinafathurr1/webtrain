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
                display: ['"IBM Plex Sans Condensed"', ...defaultTheme.fontFamily.sans],
                sans: ['"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                canvas: 'rgb(var(--color-canvas) / <alpha-value>)',
                surface: 'rgb(var(--color-surface) / <alpha-value>)',
                border: {
                    subtle: 'rgb(var(--color-border-subtle) / <alpha-value>)',
                    interactive: 'rgb(var(--color-border-interactive) / <alpha-value>)',
                },
                ink: {
                    muted: 'rgb(var(--color-text-muted) / <alpha-value>)',
                    secondary: 'rgb(var(--color-text-secondary) / <alpha-value>)',
                    primary: 'rgb(var(--color-text-primary) / <alpha-value>)',
                },
                danger: {
                    DEFAULT: 'rgb(var(--color-danger) / <alpha-value>)',
                    surface: 'rgb(var(--color-danger) / 0.08)',
                },
            },
            borderRadius: {
                DEFAULT: '5px',
            },
        },
    },

    plugins: [forms, typography],
};
