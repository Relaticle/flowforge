const preset = require('./vendor/filament/filament/tailwind.config.preset')
const colors = require('tailwindcss/colors')

module.exports = {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './resources/views/livewire/**/*.blade.php', // Add this to include Kanban components
    ],
    theme: {
        extend: {
            colors: {
                'kanban': {
                    'primary': 'var(--kanban-primary)',
                    'primary-hover': 'var(--kanban-primary-hover)',
                    'danger': 'var(--kanban-danger)',
                    'warning': 'var(--kanban-warning)',
                    'success': 'var(--kanban-success)',
                    'info': 'var(--kanban-info)',
                    'column': {
                        'bg': 'var(--kanban-column-bg)',
                        'header': 'var(--kanban-column-header)',
                        'border': 'var(--kanban-column-border)',
                    },
                    'card': {
                        'bg': 'var(--kanban-card-bg)',
                        'hover': 'var(--kanban-card-hover)',
                        'border': 'var(--kanban-card-border)',
                    },
                    'priority': {
                        'high': 'var(--kanban-priority-high)',
                        'medium': 'var(--kanban-priority-medium)',
                        'low': 'var(--kanban-priority-low)',
                    }
                },
            },
            boxShadow: {
                'kanban-card': 'var(--kanban-card-shadow-normal)',
                'kanban-card-hover': 'var(--kanban-card-shadow-hover)',
                'kanban-column': 'var(--kanban-column-shadow)',
            },
            animation: {
                'kanban-card-add': 'kanban-card-add 0.35s cubic-bezier(0.21, 1.02, 0.73, 1)',
                'kanban-card-move': 'kanban-card-move 0.25s cubic-bezier(0.22, 1, 0.36, 1)',
                'kanban-card-remove': 'kanban-card-remove 0.2s cubic-bezier(0.33, 1, 0.68, 1)',
                'kanban-column-pulse': 'kanban-column-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            },
            keyframes: {
                'kanban-card-add': {
                    '0%': { opacity: 0, transform: 'scale(0.96) translateY(10px)' },
                    '100%': { opacity: 1, transform: 'scale(1) translateY(0)' },
                },
                'kanban-card-move': {
                    '0%': { transform: 'translateY(0) scale(1.03)', boxShadow: 'var(--kanban-card-shadow-hover)' },
                    '100%': { transform: 'translateY(0) scale(1)', boxShadow: 'var(--kanban-card-shadow-normal)' },
                },
                'kanban-card-remove': {
                    '0%': { opacity: 1, transform: 'scale(1)' },
                    '100%': { opacity: 0, transform: 'scale(0.96)' },
                },
                'kanban-column-pulse': {
                    '0%, 100%': { opacity: 1 },
                    '50%': { opacity: 0.85 },
                },
            },
            transitionProperty: {
                'kanban': 'box-shadow, transform, background-color, border-color, color, fill, stroke',
                'kanban-delayed': 'box-shadow 0.15s ease, transform 0.15s ease, background-color 0.15s ease',
            },
            transitionDuration: {
                '250': '250ms',
            },
            transitionTimingFunction: {
                'kanban-bounce': 'cubic-bezier(0.34, 1.56, 0.64, 1)',
            }
        },
    },
}
