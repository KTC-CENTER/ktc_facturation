/** @type {import('tailwindcss').Config} */

// Load plugins safely
const plugins = [];
try { plugins.push(require('@tailwindcss/forms')); } catch(e) {}
try { plugins.push(require('@tailwindcss/typography')); } catch(e) {}
try { plugins.push(require('@tailwindcss/aspect-ratio')); } catch(e) {}

module.exports = {
    content: [
        './templates/**/*.html.twig',
        './assets/js/**/*.js',
        './src/Form/**/*.php',
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd',
                    400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8',
                    800: '#1e40af', 900: '#1e3a8a', 950: '#172554',
                },
                ktc: {
                    dark: '#1E3A5F',
                    blue: '#2E86AB',
                    light: '#A3C4DC'
                },
                success: { 50: '#f0fdf4', 100: '#dcfce7', 500: '#22c55e', 600: '#16a34a', 700: '#15803d' },
                warning: { 50: '#fffbeb', 100: '#fef3c7', 500: '#f59e0b', 600: '#d97706', 700: '#b45309' },
                danger:  { 50: '#fef2f2', 100: '#fee2e2', 500: '#ef4444', 600: '#dc2626', 700: '#b91c1c' },
                info:    { 50: '#eff6ff', 100: '#dbeafe', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8' },
            },
            fontFamily: {
                sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
            },
            boxShadow: {
                'soft': '0 2px 15px -3px rgba(0,0,0,.07), 0 10px 20px -2px rgba(0,0,0,.04)',
            },
        },
    },
    plugins: plugins,
}
