export default defineAppConfig({
    docus: {
        title: 'Flowforge',
        description: 'Transform any Laravel model into production-ready drag-and-drop Kanban boards.',
        header: {
            logo: {
                alt: 'Flowforge Logo',
            }
        }
    },
    seo: {
        title: 'Flowforge',
        description: 'Transform any Laravel model into production-ready drag-and-drop Kanban boards.',
    },
    github: {
        repo: 'flowforge',
        owner: 'Relaticle',
        edit: true,
        rootDir: 'docs'
    },
    socials: {
        discord: 'https://discord.gg/b9WxzUce4Q'
    },
    ui: {
        colors: {
            primary: 'violet',
            neutral: 'zinc'
        }
    },
    uiPro: {
        pageHero: {
            slots: {
                container: 'flex flex-col lg:grid py-16 sm:py-20 lg:py-24 gap-16 sm:gap-y-2'
            }
        }
    },
    toc: {
        title: 'On this page',
        bottom: {
            title: 'Ecosystem',
            edit: 'https://github.com/Relaticle/flowforge',
            links: [
                {
                    icon: 'i-simple-icons-laravel',
                    label: 'FilaForms',
                    to: 'https://filaforms.app',
                    target: '_blank'
                },
                {
                    icon: 'i-lucide-sliders',
                    label: 'Custom Fields',
                    to: 'https://relaticle.github.io/custom-fields',
                    target: '_blank'
                }
            ]
        }
    }
})
