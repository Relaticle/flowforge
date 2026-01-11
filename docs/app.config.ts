const baseURL = process.env.NUXT_APP_BASE_URL || '/'
const currentVersion = process.env.DOCS_VERSION || 'v3'

export default defineAppConfig({
    docus: {
        title: 'Flowforge',
        description: 'Transform any Laravel model into production-ready drag-and-drop Kanban boards.',
        url: `https://relaticle.github.io${baseURL}`,
        image: `${baseURL}preview.png`,
        header: {
            logo: {
                alt: 'Flowforge Logo',
                light: `${baseURL}logo-light.svg`,
                dark: `${baseURL}logo-dark.svg`
            }
        }
    },
    seo: {
        title: 'Flowforge',
        description: 'Transform any Laravel model into production-ready drag-and-drop Kanban boards.',
        ogImage: `${baseURL}preview.png`
    },
    github: {
        branch: currentVersion === 'v3' ? '3.x' : '2.x',
        repo: 'flowforge',
        owner: 'Relaticle',
        edit: true,
        rootDir: 'docs'
    },
    versioning: {
        current: currentVersion,
        versions: [
            { label: 'v3 (Latest)', value: 'v3', path: '/flowforge/' },
            { label: 'v2', value: 'v2', path: '/flowforge/v2/' }
        ]
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
                    to: 'https://custom-fields.relaticle.com',
                    target: '_blank'
                }
            ]
        }
    }
})
