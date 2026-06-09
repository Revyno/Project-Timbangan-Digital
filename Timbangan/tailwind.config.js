import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import animate from 'tailwindcss-animate';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ["class"],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        container: {
            center: true,
            padding: "2rem",
            screens: {
                "2xl": "1400px",
            },
        },
        extend: {
            colors: {
                // Minimalist light mode colors
                dashboard: {
                    primary: '#2563eb', // blue-600
                    secondary: '#3b82f6', // blue-500
                    success: '#10b981', // emerald-500
                    warning: '#f59e0b', // amber-500
                    danger: '#ef4444', // red-500
                    surface: '#ffffff', // white
                    background: '#f8fafc', // slate-50
                    text: '#0f172a', // slate-900
                    muted: '#64748b', // slate-500
                },
                // Shadcn-vue colors 
                border: "hsl(var(--border))",
                input: "hsl(var(--input))",
                ring: "hsl(var(--ring))",
                background: "hsl(var(--background))",
                foreground: "hsl(var(--foreground))",
                primary: {
                    DEFAULT: "hsl(var(--primary))",
                    foreground: "hsl(var(--primary-foreground))",
                },
                secondary: {
                    DEFAULT: "hsl(var(--secondary))",
                    foreground: "hsl(var(--secondary-foreground))",
                },
                destructive: {
                    DEFAULT: "hsl(var(--destructive))",
                    foreground: "hsl(var(--destructive-foreground))",
                },
                muted: {
                    DEFAULT: "hsl(var(--muted))",
                    foreground: "hsl(var(--muted-foreground))",
                },
                accent: {
                    DEFAULT: "hsl(var(--accent))",
                    foreground: "hsl(var(--accent-foreground))",
                },
                popover: {
                    DEFAULT: "hsl(var(--popover))",
                    foreground: "hsl(var(--popover-foreground))",
                },
                card: {
                    DEFAULT: "hsl(var(--card))",
                    foreground: "hsl(var(--card-foreground))",
                },
            },
            borderRadius: {
                lg: "var(--radius)",
                md: "calc(var(--radius) - 2px)",
                sm: "calc(var(--radius) - 4px)",
            },
            // IBM Plex Sans font family from SKILL.md
            fontFamily: {
                sans: ['IBM Plex Sans', 'Inter', ...defaultTheme.fontFamily.sans],
                display: ['IBM Plex Sans', 'Inter', ...defaultTheme.fontFamily.sans],
                mono: ['IBM Plex Mono', 'ui-monospace', 'SFMono-Regular', ...defaultTheme.fontFamily.mono],
            },
            // Typography scale from SKILL.md: 12/14/16/20/24/32
            fontSize: {
                'dashboard-xs': ['12px', { lineHeight: '16px' }],
                'dashboard-sm': ['14px', { lineHeight: '20px' }], 
                'dashboard-base': ['16px', { lineHeight: '24px' }],
                'dashboard-lg': ['20px', { lineHeight: '28px' }],
                'dashboard-xl': ['24px', { lineHeight: '32px' }],
                'dashboard-2xl': ['32px', { lineHeight: '40px' }],
            },

            // Glass panel effects
            backdropBlur: {
                'glass': '8px',
                'glass-md': '12px',
                'glass-lg': '16px',
            },
            boxShadow: {
                'glass': '0 8px 32px 0 rgba(31, 38, 135, 0.37)',
                'glass-lg': '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
            },
            keyframes: {
                "accordion-down": {
                    from: { height: 0 },
                    to: { height: "var(--radix-accordion-content-height)" },
                },
                "accordion-up": {
                    from: { height: "var(--radix-accordion-content-height)" },
                    to: { height: 0 },
                },
                // Subtle animations for glass panels
                "float": {
                    "0%, 100%": { transform: "translateY(0px)" },
                    "50%": { transform: "translateY(-4px)" },
                },
                "glow": {
                    "0%, 100%": { boxShadow: "0 0 20px rgba(12, 92, 171, 0.3)" },
                    "50%": { boxShadow: "0 0 40px rgba(12, 92, 171, 0.5)" },
                },
            },
            animation: {
                "accordion-down": "accordion-down 0.2s ease-out",
                "accordion-up": "accordion-up 0.2s ease-out",
                "float": "float 3s ease-in-out infinite",
                "glow": "glow 2s ease-in-out infinite",
            },
        },
    },

    plugins: [forms, animate],
};
