const baseURL = process.env.NUXT_APP_BASE_URL || '/'

export default defineAppConfig({
    docus: {
        title: 'Flowforge',
        description: 'Transform any Laravel model into production-ready drag-and-drop Kanban boards.',
        url: 'https://relaticle.github.io/flowforge/',
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
        branch: '2.x',
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
    }
})
