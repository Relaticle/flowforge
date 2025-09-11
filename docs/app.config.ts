export default defineAppConfig({
    docus: {
        title: 'Flowforge',
        description: 'Transform any Laravel model into production-ready drag-and-drop Kanban boards.',
        url: 'https://flowforge.dev',
        image: process.env.NODE_ENV === 'production' ? '/flowforge/preview.png' : '/preview.png',
        github: {
            dir: 'docs/content',
            branch: '2.x',
            repo: 'flowforge',
            owner: 'Relaticle',
            edit: true
        },
        header: {
            logo: {
                alt: 'Flowforge Logo',
                light: '/flowforge/logo-light.svg',
                dark: '/flowforge/logo-dark.svg'
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
    }
})