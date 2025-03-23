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
                    'column': {
                        'bg': 'var(--kanban-column-bg)',
                        'header': 'var(--kanban-column-header)',
                    },
                    'card': {
                        'bg': 'var(--kanban-card-bg)',
                        'hover': 'var(--kanban-card-hover)',
                        'shadow': 'var(--kanban-card-shadow)',
                    },
                },
            },
            boxShadow: {
                'kanban-card': 'var(--kanban-card-shadow-normal)',
                'kanban-card-hover': 'var(--kanban-card-shadow-hover)',
                'kanban-column': 'var(--kanban-column-shadow)',
            },
            animation: {
                'kanban-card-add': 'kanban-card-add 0.3s ease-out',
                'kanban-card-move': 'kanban-card-move 0.2s ease-in-out',
            },
            keyframes: {
                'kanban-card-add': {
                    '0%': { opacity: 0, transform: 'translateY(10px)' },
                    '100%': { opacity: 1, transform: 'translateY(0)' },
                },
                'kanban-card-move': {
                    '0%': { transform: 'scale(1.02)' },
                    '100%': { transform: 'scale(1)' },
                },
            },
            transitionProperty: {
                'kanban': 'box-shadow, transform, background, border',
            }
        },
    },
}
