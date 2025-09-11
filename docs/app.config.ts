const baseURL = process.env.NODE_ENV === 'production' ? '/flowforge/' : '/'

export default defineAppConfig({
    docus: {
        title: 'Flowforge',
        description: 'Transform any Laravel model into production-ready drag-and-drop Kanban boards.',
        url: 'https://relaticle.github.io/flowforge/',
        image: `${baseURL}preview.png`,
        github: {
            branch: '2.x',
            repo: 'flowforge',
            owner: 'Relaticle',
            edit: true,
            rootDir: 'docs'
        },
        header: {
            logo: {
                alt: 'Flowforge Logo',
                light: `${baseURL}logo-light.svg`,
                dark: `${baseURL}logo-dark.svg`
            },
            showLinkIcon: false,
            fluid: false,
            iconLinks: [
                {
                    href: 'https://github.com/relaticle/flowforge',
                    icon: 'simple-icons:github'
                }
            ]
        }
    },
    socials: {
        github: 'https://github.com/Relaticle/flowforge',
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
